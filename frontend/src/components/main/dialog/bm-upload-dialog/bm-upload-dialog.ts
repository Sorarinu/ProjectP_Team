import * as Vue from 'vue';
import Component from 'vue-class-component';
/**
 * ブックマークファイルをアップロードするダイアログ
 */
@Component({
    template: require('./bm-upload-dialog.pug'),
})
export class BmUploadDialog extends Vue {

    formData: FormData;

    fileChange(ev: Event) {
        // console.log('called' + ev);
        this.formData = new FormData();
        // TODO: formdataをボタンクリックで作ってActionへ流す
    }

    get show () {
        return this.$store.state.showUploadDialog;
    }

    // upload() {
    //     this.uploadBookmarkAct(null);
    //     this.closeDialog();
    // }

    // @Action(Actions.uploadBookmark)
    // uploadBookmarkAct(formData: FormData) {
    //     return;
    // }

    closeDialog() {
        this.$store.dispatch('closeUploadDialog');
    }
}
