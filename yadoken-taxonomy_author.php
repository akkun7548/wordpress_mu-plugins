<?php
/*
Plugin Name: カスタムタクソノミー「投稿者」追加・編集プラグイン
Description: 同一のアカウントを複数人で共有している際に、記事に投稿者を設定するためのタクソノミーを追加しています。
Version: 0.1
Author: 野生動物研究会
Auhtor URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * 投稿者名
 * 
 * - init
 *  - カスタムタクソノミー「投稿者」を登録
 * 
 * - admin_enqueue_scripts
 *  - edit.phpにインラインスタイル・スクリプトを出力
 * 
 * - gettext
 *  - 'Author'の翻訳を「アカウント」に上書き
 * 
 * - manage_posts_columns
 *  - 「投稿者」のカラムの順番を変更
 */


/**
 * カスタムタクソノミー「投稿者名」追加
 * 
 * やどけんwebでは単一の投稿用アカウントを複数のユーザーで共用しているため、その投稿記事に
 * 投稿者名を表示するカスタムタクソノミーを設定しています。
 */
add_action( 'init', function() {
  
  // 登録
  register_taxonomy(
    'yadoken_author',
    'post',
    array(
      'label' => '投稿者',
      'labels' => array(
        'all_items' => '投稿者一覧',
        'add_new_item' => '投稿者を追加',
        'edit_item' => '投稿者を編集',
        'add_or_remove_items' => '投稿者の変更もしくは削除'
      ),
      'public' => true,
      'show_ui' => true,
      'show_in_nav_menus' => true,
      'show_in_quick_edit' => true,
      'show_admin_column' => true,
      'description' => '記事の投稿者による分類です。',
      'hierarchical' => false,
      'show_in_rest' => true,
      'rewrite' => array(
        'slug' => 'author_name',
        'with_front' => true
      )
    )
  );

});


/**
 * edit.phpにインラインスタイル・スクリプトを出力
 * 
 * 新たに追加した「投稿者」カラムの幅を追加しています。
 * 
 * クイック編集の入力欄に現在の値を入力するスクリプトを追加しています。
 * 
 * @param string $hook_suffix  現在の管理画面
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {

  // 現在の管理画面がedit.phpの場合
  if( $hook_suffix === 'edit.php' ) {

    // 出力するcss
    $css = '.column-taxonomy-yadoken_author { width: 10%; }';

    /**
     * インラインスタイルを追加
     * 
     * 第一引数のハンドル名のスタイルシートが読み込まれている時、第二引数の文字列がインラインスタイル
     * として出力されます。
     */
    wp_add_inline_style( 'wp-admin', $css );

  }

});


/**
 * 'Author'の翻訳を「アカウント」に上書き
 * 
 * @param string $translation  翻訳後のテキスト
 * @param string $text         翻訳前のテキスト
 * @param string $domain       テキストドメイン
 * @return string  翻訳後のテキスト
 */
add_filter( 'gettext', function( $translation, $text, $domain ) {

  // デフォルトのテキストドメインかつ'Author'の場合
  if( $domain === 'default' && $text === 'Author' ) {
    $translation = 'アカウント';
  }

  return $translation;

}, 10, 3 );


/**
 * 「投稿者」のカラムの順番を変更
 * 
 * フック名：manage_{$post_type}_posts_columns
 * 
 * @param string[] $post_columns  列のスラッグと名前の連想配列
 * @return string[]
 */
add_filter( 'manage_post_posts_columns', function( $post_columns ) {

  // 'title'もしくは'taxonomy-yadoken_author'カラムが存在しない場合は何もしない
  if( ! isset( $post_columns['title'] )
  || ! isset( $post_columns['taxonomy-yadoken_author'] ) ) {
    return $post_columns;
  }

  // 値を保存
  $yadoken_author = $post_columns['taxonomy-yadoken_author'];

  // 配列から一旦削除
  unset( $post_columns['taxonomy-yadoken_author'] );

  // 新しい「列のスラッグと名前の連想配列」
  $new_posts_columns = array();

  // 配列の並び順を変更するためのループ
  foreach( $post_columns as $column_name => $column_display_name ) {

    // 配列をそのままコピー
    $new_posts_columns[$column_name] = $column_display_name;

    // タイトルの後に挿入
    if( $column_name === 'title' ) {
      $new_posts_columns['taxonomy-yadoken_author'] = $yadoken_author;
    }

  }
  
  return $new_posts_columns;

});

?>