<?php

namespace App\Http\Controllers\MyPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Mypage\Profile\EditRequest;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

//プロフィール編集画面
class ProfileController extends Controller
{
    public function showProfileEditForm()
    {
        return view('mypage.profile_edit_form')
            ->with('user', Auth::user());
    }

    //プロフィール保存処理
    public function editProfile(EditRequest $request)
    {
        //ログインしているユーザの情報を取得
        $user = Auth::user();
        //フォームのニックネーム欄に入力された文字列を取得
        $user->name = $request->input('name');

        //アバター画像が指定されている場合(値が入力されているかをチェック)
        if ($request->has('avatar')) {
            //アバター画像をストレージに保存（saveAvatarメソッド）
            //アップロードされた画像の情報を取得
            $fileName = $this->saveAvatar($request->file('avatar'));
            //ファイル名をDBに保存
            $user->avatar_file_name = $fileName;
        }
        //DBに保存
        $user->save();

        return redirect()->back()
            ->with('status', 'プロフィールを変更しました。');
    }

    /**
     * アバター画像をリサイズして保存します
     *
     * @param UploadedFile $file アップロードされたアバター画像
     * @return string ファイル名
     */
    private function saveAvatar(UploadedFile $file): string
    {
        //一時ファイルを生成してパスを取得する(makeTempPathメソッド)
        $tempPath = $this->makeTempPath();

        //Intervention Imageを使用して(Image::make())、
        //画像をリサイズ後(fitメソッド)、一時ファイルに保存
        Image::make($file)->fit(200, 200)->save($tempPath);

        //Storageファサードを使用して画像をディスクに保存
        //Filesystemクラスのインスタンスを取得できる
        $filePath = Storage::disk('public')

            //ディスクを取得したら、putFileメソッドで画像を保存
            ->putFile('avatars', new File($tempPath));

        return basename($filePath);
    }

    /**
     * 一時的なファイルを生成してパスを返します。
     *
     * @return string ファイルパス
     */
    private function makeTempPath(): string
    {
        //一時ファイルを生成(/tmpに一時ファイルが生成され、そのファイルポインタが返される)
        $tmp_fp = tmpfile();
        //ファイルのメタ情報を取得
        $meta   = stream_get_meta_data($tmp_fp);
        //メタ情報からURI(ファイルのパス)を取得し、返す
        return $meta["uri"];
    }
}
