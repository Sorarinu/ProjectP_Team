# クライアント-サーバー間リクエストのフォーマット
> APIの仕様でもある

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Init <*Done*>](#init処理-done)
- [サインアップ <*Done*>](#%E3%82%B5%E3%82%A4%E3%83%B3%E3%82%A2%E3%83%83%E3%83%97-done)
- [サインイン <*Done*>](#%E3%82%B5%E3%82%A4%E3%83%B3%E3%82%A4%E3%83%B3-done)
- [サインアウト <*Done*>](#%E3%82%B5%E3%82%A4%E3%83%B3%E3%82%A2%E3%82%A6%E3%83%88-done)
- [Bookmarkアップロード(Add-on)](#bookmarkアップロードadd-on)
- [Bookmarkファイルアップロード <*Done*>](#bookmark%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%83%E3%83%97%E3%83%AD%E3%83%BC%E3%83%89-done)
- [Bookmarkファイルエクスポート <*Done*>](#bookmark%E3%83%95%E3%82%A1%E3%82%A4%E3%83%AB%E3%82%A8%E3%82%AF%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%88)
- [Bookmark情報 CRUD](#bookmark%E6%83%85%E5%A0%B1-crud)
  - [作成 <*Done*>](#%E4%BD%9C%E6%88%90)
  - [更新 <*Done*>](#%E6%9B%B4%E6%96%B0)
  - [削除 <*Done*>](#%E5%89%8A%E9%99%A4)
  - [取得1(ブックマーク全取得) <*Done*>](#%E5%8F%96%E5%BE%971%E3%83%96%E3%83%83%E3%82%AF%E3%83%9E%E3%83%BC%E3%82%AF%E5%85%A8%E5%8F%96%E5%BE%97)
  - [取得2(リソース指定取得)](#%E5%8F%96%E5%BE%972%E3%83%AA%E3%82%BD%E3%83%BC%E3%82%B9%E6%8C%87%E5%AE%9A%E5%8F%96%E5%BE%97)
- [類似度検出](#%E9%A1%9E%E4%BC%BC%E5%BA%A6%E6%A4%9C%E5%87%BA)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Init処理 <*Done*>
* メソッド : GET
* URL : /api/v1/init
* パラメータ : なし
* 応答 : JSON
 * status : string
 "OK" or "NG"
 * message : string

*初回アクセス時に必ずコールしてください*
 
# サインアップ <*Done*>
* メソッド : POST
* URL : /api/v1/signup
* パラメータ :
 * email : string
 * password : string (min6)
* 応答 : JSON
 * status : string  
 要求が成功したかどうか "OK" or "NG"
 * message :string  
 詳細を適当にわかりやすく そのままAlertで表示します

サンプルレスポンス1 (サインアップ成功例)
 ```json
 {
   "status" : "OK",
   "message" : "[user_id] created"
 }
 ```
 サンプルレスポンス2(サインアップ失敗)
 ```json
 {
   "status" : "NG",
   "message" : "パスワードが短いですよ?"
 }
 ```


# サインイン <*Done*>
* メソッド : POST
* URL : /api/v1/signin
* パラメータ :
 * email : string
 * password :string (min6)
* 応答 : JSON
 * status : string  
 "OK" or "NG"
 * message : string

サインアップと同じ感じでお願いします


# サインアウト <*Done*>

* メソッド : GET
* URL : /api/v1/singout
* パラメータ : なし
* 応答 : JSON
 * status : string  
 "OK" or "NG"
 * message : string

サインアップと同じ感じでお願いします

# Bookmarkアップロード(Add-on)
* メソッド : POST
* URL : /api/v1/bookmarks/upload/addon
* パラメータ : JSON
 * data : ブックマークの一覧
```JSON(例)
{
  "title" : "Twitter",
  "detail" : "",
  "reg_date" : "2016/09/18 20:38:00",
  "parent_id" : "1",
  "folder" : "false",
  "url" : "https://twitter.com/"
},
{
  "title" : "Twitter",
  "detail" : "",
  "reg_date" : "2016/09/18 20:38:00",
  "parent_id" : "1",
  "folder" : "false",
  "url" : "https://twitter.com/"
},
{
  "title" : "Twitter",
  "detail" : "",
  "reg_date" : "2016/09/18 20:38:00",
  "parent_id" : "1",
  "folder" : "false",
  "url" : "https://twitter.com/"
}
```
* 応答 : JSON
```JSON
{
  "status" : "OK" or "NG",
  "message" : "",
  "code" : 200 or ***
}
```

# Bookmarkファイルアップロード <*Done*>
* メソッド : POST
* URL : /api/v1/bookmarks/upload 
* パラメータ名 : bmfile
* enc-type : multipart/form-data
* 送るファイル : ブックマークのブラウザからエクスポートしたファイル
* 応答 : 解析されたブックマークデータと応答 JSON

JSONはこんな感じで statusがNGならbookmarkはなくていいよ
```json
{
  "status" : "OK" ,
  "message" : "File loaded", 
  "bookmark" : [{
    "id" : "1",
    "title" : "Programming",
    "detail" : "プログラミング関連ページ",
    "reg_date" : "2006/01/22 20:20:12",
    "folder" : "true",
    "bookmark": [
      {
        "id" : "2",
        "parent_id" : "1",
        "title" : "Effective Java",
        "detail" : "JavaでプログラミングをするすべてのSE必読の書籍",
        "reg_date":"2014/03/11 10:10:10",
        "folder": "false",
        "url": "https://www.amazon.co.jp/dp/4621066056"
      },
      {
        "id" : "3",
        "parent_id" : "1",
        "title": "リーダブルコード",
        "detail" : "より良いコードを書くためのシンプルで実践的なテクニック",
        "reg_date" : "2014/03/11 10:10:10",
        "folder" : "false",
        "url" : "https://www.amazon.co.jp/dp/4873115655"
      },
      {
        "id" : "4",
        "parent_id" : "1",
        "title": "Androidまとめ",
        "detail" : "",
        "reg_date" : "2016/03/22 10:10:10",
        "folder" : "true",
        "bookmark" : [
          {
            こんな感じで
          }
        ]
      },
    ]

  }]
}


```

# Bookmarkファイルエクスポート <*Done*>
* メソッド : GET
* URL : /api/v1/bookmarks/export/{browser_type}
* パラメータ :
 * browser_type : string  
 エクスポートしてほしいブラウザの種類 firefoxとかchromeとかsafariとかを指定
* 応答
 * 指定したブラウザでインポート可能なブックマークファイルが送られてこればいい


# Bookmark情報 CRUD

## 作成 <*Done*>
* メソッド : POST
* URL : /api/v1/bookmarks
* パラメータ : JSON

例
* folderならfolderがtrueになりurlはないか空文字列
* parent_idは親の要素のid つまり親のディレクトリのid

```JSON
{
  "title" : "Twitter",
  "detail" : "",
  "reg_date" : "2016/09/18 20:38:00",
  "parent_id" : "1",
  "folder" : "false",
  "url" : "https://twitter.com/"
}

```
* 応答:JSON

例 
* 作成に成功した場合idはブックマークのID
* 失敗したらstatusがNGでmessageに原因
```json
{
  "status" : "OK" ,
  "message" : "",
  "id" : "4423"
}
```


## 更新 <*Done*>
* メソッド:PUT
* URL: /api/v1/bookmarks/[bookmark_id]
* パラメータ:JSON  

* 応答:JSON
 * status : string  
 "OK" or "NG"
 * message : string

## 削除 <*Done*>
* メソッド:DELETE
* URL: /api/v1/bookmarks/[bookmark_id]
* パラメータ:なし
* 応答:JSON
 * status : string  
 "OK" or "NG"
 * message : string

## 取得1(ブックマーク全取得) <*Done*>
* メソッド:GET
* URL: /api/v1/bookmarks/
* パラメータ:なし
* 応答: JSON
ブックマークファイルのアップロードの時と同じ感じの形式のJSONがこればいいです
ユーザーのブックマーク全部JSONで送ってください


## 取得2(リソース指定取得)
これ正直いらないかもしれない  
/api/v1/bookmarks/[bookmark_id] にGETでデータとれるやつ



# 類似度検出
* メソッド : POST
* URL : /api/v1/similarity-search/
* パラメータ : JSON
パラメータというかJSON文字列入れて送ります
Content-Type:application/json

```json
{
  "search_word":"Java",
  "bookmark" : [
    {
      "id" : "1",
      "url" : "http://hoge.co.jp"
    },
    {
      "id" : "2",
      "url" : "http://notfound.address.co.jp"
    },
    {
      "id" : "3",
      "url" : "http://www.oracle.com/jp/java/overview/index.html"
    },
  ]
}

```
サーバー側にブックマークデータあるわけだからURLとかは正直送らなくてもいいかもしれない.
でも対象を絞ったり簡単にできるぶんクライアント側から飛ばした方がいいか・・・

* 応答 : JSON
送られたJSONのbookmarkの各要素に結果付与して返却

例 (上の例を送ったとき)
```json
{
  "search_word":"Java",
  "bookmark" : [
    {
      "id" : "1",
      "url" : "http://hoge.co.jp",
      "similarity_rate":"0.1",
      原因とかいろいろでーたいるなら
    },
    {
      "id" : "2",
      "url" : "http://notfound.address.co.jp",
      "similarity_rate":"0",
      あくせすできなかったよとかいるなら付与
    },
    {
      "id" : "3",
      "url" : "http://www.oracle.com/jp/java/overview/index.html",
      "similarity_rate":"0.95"
    }
  ]
}
```
