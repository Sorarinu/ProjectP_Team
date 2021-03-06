import {Bookmark} from '../model/bookmark';
import {BookmarkService} from './bookmark-service';
/**
 * ブックマーク操作APIコールの実装
 * ローカルで完結するMock
 */
export class MockBookmarkService implements BookmarkService {

    /**
     * BookmarkRoot
     */
    rootBM: Bookmark;

    private PROCESS_TIME = 500;
    private STORAGE_KEY = 'Bookmark-data';

    constructor() {
        // bookmark初期化.
        localStorage.clear();
        const mockData = require('./mock-data/bookmark.json');
        this.rootBM = Bookmark.fromJSON(JSON.stringify(mockData.bookmark));
        this.saveLocalStorage();
    }

    postBookmark(bm: Bookmark, requestListener: RequestListener): void {
        // bookmarkをローカルストレージへ追加する処理.

        // bookmarkにIDを付与.
        // 本番ではサーバーからIDは与えられるが今回は時刻をIDにする
        const id = Date.now();
        bm.id = id;
        this.rootBM.addChild(bm);

        this.saveLocalStorage();

        //  応答
        setTimeout(() => {
            requestListener.ok(bm);
        }, this.PROCESS_TIME);
    }

    getBookmarks(requestListener: RequestListener): void {
        // localstorageからbookmark取り出して返す.
        const bookmark: Bookmark = Bookmark.fromJSON(localStorage.getItem(this.STORAGE_KEY));
        this.rootBM = bookmark;

        // 応答
        setTimeout(() => {
            requestListener.ok(bookmark);
        }, this.PROCESS_TIME);

    }

    updateBookmark(bm: Bookmark, requestListener: RequestListener): void {
        // 置き換え
        let target = this.rootBM.searchAll(bm.id);
        target = bm;
        this.saveLocalStorage();

        // 応答
        setTimeout(() => {
            requestListener.ok(bm);
        }, this.PROCESS_TIME);
    }

    deleteBookmark(bm: Bookmark, requestListener: RequestListener): void {
        // 削除
        const target = this.rootBM.searchAll(bm.id);
        target.remove();
        this.saveLocalStorage();

        // 応答
        setTimeout(() => {
            requestListener.ok(bm);
        }, this.PROCESS_TIME);
    }

    uploadBookmark(formData: FormData, requestListener: RequestListener): void {
        setTimeout(() => {
            requestListener.ok(this.rootBM);
        }, 500);
    }

    private saveLocalStorage() {
        localStorage.setItem(this.STORAGE_KEY, Bookmark.toJSON(this.rootBM));
    }
}
