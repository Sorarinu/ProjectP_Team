<?php
/**
 * Created by PhpStorm.
 * User: Sorarinu
 * Date: 2016/09/19
 * Time: 20:30
 */

namespace App\Http\Controllers\Api;

use App\Db_Bookmark;
use App\Http\Controllers\Controller;
use App\Library\Bookmark;
use App\Library\BookmarkUpload;
use App\User;
use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Library\Knp\Snappy\SnappyImage;
use Intervention\Image\Facades\Image;
use Log;
use Cache;
use Maknz\Slack\Facades\Slack;
use App\Library\BookmarkParser;
use App\Library\Tree;
use App\Library\BookmarkExport;
use App\Library\BookmarkDB;
use Illuminate\Http\JsonResponse;
use Psy\Util\Json;

class ApiController extends Controller
{
    public $request;
    private $fs;
    private $html = '';

    public function __construct(Request $request, Filesystem $fs)
    {
        $this->request = $request;
        $this->fs = $fs;
    }

    /**
     * ユーザのセッションIDをセッションに保存する
     * 非ログインユーザの場合には，これをUserIdとして使用する
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function init(Request $request)
    {
        $isLogin = $this->request->session()->get('isLogin');

        try {
            if (!$isLogin) {
                $this->request->session()->put('user_id', $request->session()->get('_token'));
                Slack::send('init');
                return new JsonResponse(
                    [
                        'status' => 'OK',
                        'login' => false,
                        'email' => $request->session()->get('_token'),
                        'message' => 'saved session id.'
                    ]
                );
            } else {
                return new JsonResponse(
                    [
                        'status' => 'OK',
                        'login' => true,
                        'email' => $request->session()->get('email'),
                        'message' => 'user was logged in.'
                    ]
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * サインアップ
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function signUp()
    {
        $errors = '';

        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ];

            $validation = \Validator::make($this->request->all(), $rules);

            if ($validation->passes()) {
                $data = json_decode(json_encode([
                    'password' => $this->request->input('password'),
                    'email' => $this->request->input('email')
                ]));

                $user = new User;
                $user->email = $data->email;
                $user->password = Hash::make($data->password);
                $user->save();

                Slack::send('New user has been created！ This Email Address is *' . $data->email . '*.');

                return new JsonResponse([
                    'status' => 'OK',
                    'message' => $data->email . ' created.'
                ]);
            }

            foreach ($validation->errors()->all() as $error) {
                $errors .= $error;
            }

            return new JsonResponse([
                'status' => 'NG',
                'message' => $errors
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getCode() === '23000' ?
                    'This Email address is already registered.' :
                    $e->getMessage()
            ], 400);
        }
    }

    /**
     * サインイン
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function signIn()
    {
        $errors = '';

        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ];

            $validation = \Validator::make($this->request->all(), $rules);

            if ($validation->passes()) {
                $data = json_decode(json_encode([
                    'password' => $this->request->input('password'),
                    'email' => $this->request->input('email')
                ]));

                $user = User::where('email', $data->email)
                    ->firstOrFail();

                if (Hash::check($data->password, $user->password)) {

                    $this->request->session()->put('email', $user->email);
                    $this->request->session()->put('user_id', $user->email);
                    $this->request->session()->put('isLogin', true);

                    Slack::send('User was Logged in at *' . $this->request->session()->get('user_id') . '*.');

                    return new JsonResponse([
                        'status' => 'OK',
                        'message' => 'Login success: ' . $this->request->session()->get('user_id')
                    ]);
                }

                throw new \Exception;
            }

            foreach ($validation->errors()->all() as $error) {
                $errors .= $error;
            }

            return new JsonResponse([
                'status' => 'NG',
                'message' => $errors
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => 'User Login is Failed. ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * サインアウト
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function signOut()
    {
        if ($this->request->session()->has('email')) {
            $this->request->session()->forget('email');
            $this->request->session()->forget('user_id');
            $this->request->session()->put('isLogin', false);
            return new JsonResponse([
                'status' => 'OK',
                'message' => 'Logged out.'
            ]);
        }

        return new JsonResponse([
            'status' => 'NG',
            'message' => 'This user is not authenticated.'
        ], 400);
    }

    /**
     * ブックマークファイル(HTML)のアップロード
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $bookmarkDB = new BookmarkDB($this->request);
        $filePath = $this->request->file('bmfile')->getRealPath();
        $upload = new BookmarkUpload($this->request);

        try {
            $parser = new BookmarkParser();
            $bookmarks = $parser->parseFile($filePath);
            $bookmarkJson = $upload->makeBookmarkJson($bookmarks);
            $bookmarkDB->insertDB($bookmarkJson);

            Slack::send('Bookmark Upload Success!!!! Year!!!!!! *' . $request->session()->get('user_id') . '*.');

            return new JsonResponse($bookmarkJson);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * アドオンから送られてきたブックマークのアップロード
     * 
     * @param Request $request
     */
    public function uploadAddon(Request $request)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $this->request->session()->get('user_id');

        if (BookmarkDB::checkExists($userId)) {
            BookmarkDB::deleteOldData($userId);
        }

        foreach ($data as $item) {
            $dbBookmark = new Db_Bookmark();
            $dbBookmark->bookmark_Id = $item['id'];
            $dbBookmark->user_id = $userId;
            if (isset($item['parentId'])) {
                $dbBookmark->parent_id = $item['parentId'];
            }
            $dbBookmark->title = $item['title'];
            $dbBookmark->detail = '';
            $dbBookmark->reg_date = Carbon::createFromTimestamp($item['reg_date']);
            $dbBookmark->folder = $item['folder'];

            if ($item['folder'] !== true) {
                $dbBookmark->url = $item['url'];
            }
            
	    $dbBookmark->save();
	    $this->firstCallSnapApi($item['url'], 350);
        }

        Slack::send('Bookmark Upload Success!!!! Year!!!!!! *' . $userId . '*.');
    }

    /**
     * ブラウザでインポートできる形式でエクスポートする
     *
     * @param $browserType
     * @return mixed
     */
    public function export($browserType)
    {
        $bookmarkExport = new BookmarkExport();
        $prevId = null;
        $this->html .= $bookmarkExport->addHtmlHeaders($browserType);
        $bookmarks = Bookmark::getAllBookmarkByDB($this->request);
        $this->html .= $bookmarkExport->makeExportData($bookmarks, $this->html, null, $browserType);
        $bookmark = $bookmarkExport->getLocalBookmarkResource($this->html);

        return $bookmark;
    }

    /**
     * 新規ブックマークをデータベースに登録する
     *
     * @return JsonResponse
     */
    public function create()
    {
        try {
            $json = json_decode(file_get_contents('php://input'));

            $bookmark = new Db_Bookmark();
            $bookmark->title = $json->title;
            $bookmark->detail = $json->detail;
            $bookmark->reg_date = $json->reg_date;
            $bookmark->parent_id = $json->parent_id;
            $bookmark->folder = $json->folder;
            $bookmark->url = $json->url;
            $bookmark->save();

            $bookmark = Db_Bookmark::where('title', $json->title)
                ->where('detail', $json->detail)
                ->where('url', $json->url)
                ->firstOrFail();

            return new JsonResponse([
                'status' => 'OK',
                'message' => '',
                'id' => $bookmark['id']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 指定されたBookmarkIDをもつブックマークの情報を更新する
     *
     * @param $bookmarkId
     * @return JsonResponse
     */
    public function update($bookmarkId)
    {
        try {
            $json = json_decode(file_get_contents('php://input'));
            $bookmark = Db_Bookmark::find($bookmarkId);
            $bookmark->title = $json->title;
            $bookmark->detail = $json->detail;
            $bookmark->reg_date = $json->reg_date;
            $bookmark->parent_id = $json->parent_id;
            $bookmark->folder = $json->folder;
            $bookmark->url = $json->url;
            $bookmark->save();

            return new JsonResponse([
                'status' => 'OK',
                'message' => 'Update Success.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 指定されたBookmarkIDをもつブックマークをデータベースから削除する
     *
     * @param $bookmarkId
     * @return JsonResponse
     */
    public function delete($bookmarkId)
    {
        try {
            $bookmark = Db_Bookmark::find($bookmarkId);
            $bookmark->delete();

            return new JsonResponse(['status' => 'OK']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'NG',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 全てのブックマークを取得する
     */
    public function getAll()
    {
        return json_encode(Bookmark::getAllBookmarkByDB($this->request), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * ブックマークアップロードの時にAPIサーバへサムネ取得のリクエストを投げる
     */
    public function firstCallSnapApi($url, $w)
    {
        $url = uelencode($url);
	$apiUrl = 'https://s.wordpress.com/mshots/v1/' . $url . '?w=' . $w;

        $options = [
            CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_FOLLOWLOCATION => true,
	    CURLOPT_AUTOREFERER => true,
	]

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt_array($ch, $options);
	curl_exec($ch);

	return;
    }

    /**
     * 指定されたホームページのスクショを取得する
     *
     * @return mixed
     */
    public function snap()
    {
	$w = 350;
        $url = $this->request->input('url');
	//$apiUrl = 'http://capture.heartrails.com/350x300?' . $url;
	$apiUrl = 'https://s.wordpress.com/mshots/v1/' . $url . '?w=' . $w;
        $image = Image::make(file_get_contents($apiUrl));
        return $image->response('jpg');
    }

    /**
     * 類似度のやつ
     *
     * @return mixed
     */
    public function similarity()
    {
        //$res = null;
        $json = json_encode(json_decode(file_get_contents('php://input')), JSON_UNESCAPED_SLASHES);
        $url = 'http://127.0.0.1:8089/api/v1/similarity-search/';

        $options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_AUTOREFERER => true,
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
    }
}
