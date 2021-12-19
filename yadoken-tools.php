<?php
/*
Plugin Name: 開発用ツール
Description: wordpressの動作そのものには変更を加えません。
Version: 0.1
Author: 野生動物研究会
Auhtor URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * - admin_print_styles/yadoken_inspect_enqueued_styles
 * - wp_print_styles/yadoken_inspect_enqueued_styles
 *  - 現在のキューに加えられているスタイルシートのハンドル名を出力
 * 
 * - wp_print_scripts
 *  - 現在のキューに加えられているスクリプトのハンドル名を出力
 * 
 * - shutdown
 *  - 現在登録されているアクションフック・フィルター名と登録数をコンソールに表示
 * 
 * - init
 * - rewrite_rules_array
 *  - コンソール出力を利用してWP_Rewriteインスタンス、リライトルールを確認
 * 
 * - admin_init
 *  - 権限が投稿者のアカウントから非公開の記事を表示する権限を削除
 * 
 * - admin_init
 *  - 権限が投稿者のアカウントから固定ページの下書きを作成する権限を削除
 */


/**
 * 現在のキューに加えられているスタイルシートのハンドル名を出力
 */

add_action( 'admin_print_styles', 'yadoken_inspect_enqueued_styles', 9999 );
add_action( 'wp_print_styles', 'yadoken_inspect_enqueued_styles', 9999 );
function yadoken_inspect_enqueued_styles() {

  // WP_Styles
  global $wp_styles;

  // ハンドル名配列を結合して出力
  echo '<script>console.log( "スタイルシート:' . join( ' | ', $wp_styles->queue ) . ' ")</script>';
  
}
/** コードの上端にあるコメントアウトを消すことで使用できます。 */


/**
 * 現在のキューに加えられているスクリプトのハンドル名を出力
 */

add_action( 'wp_print_scripts', function() {
  
  // WP_Scripts
  global $wp_scripts;

  // ハンドル名配列を結合して出力
  echo '<script>console.log( "スクリプト:' . join( ' | ', $wp_scripts->queue ) . '" )</script>';

}, 9999 );
/** コードの上端にあるコメントアウトを消すことで使用できます。 */


/**
 * 現在登録されているアクションフック・フィルター名と登録数をコンソールに表示
 * 
 * 一番最後のアクションフックで実行することで一覧としています。
 */
/*
add_action( 'shutdown', function() {

  // アクションフックの配列
  global $wp_actions;

  // 出力するスクリプト
  $console = '';

  // アクションフック名： 登録数 の文字列を作成
  foreach($wp_actions as $action => $count) {
    $console .= "$action: $count\\n";
  }

  // スクリプトを出力
  echo '<script>console.log( "' . $console . '" );</script>';

});
/** コードの上端にあるコメントアウトを消すことで使用できます。 */


/**
 * リライトルールの確認用コード
 * 
 * このコードのコメントアウトを解除することで、リライトルールの配列をコンソールに出力することでができます。
 * 
 * $wp_rewrite->flush_rules( false )は負荷が大きい操作のため、デバッグ時以外は「必ず」
 * コメントアウトするようにしてください。
 */
/*
add_action( 'init', function() {
  global $wp_rewrite;

  // コンソールにWP_Rewriteオブジェクトを出力
  echo '<script>console.log( \'' . str_replace( "\n", "\\n", json_encode( $wp_rewrite, JSON_PRETTY_PRINT ) ) . '\' )</script>';

  $wp_rewrite->flush_rules( false );  

});
add_filter( 'rewrite_rules_array', function( $rules ) {
  
  // コンソールにリライトルールを出力
  echo '<script>console.log( "' . join( '\n', $rules ) . '" )</script>';

  return $rules;

});
/** コードの上端にあるコメントアウトを消すことで使用できます。 */


/**
 * 投稿者の非公開記事表示権限削除
 * 
 * yadoken_allow_author_read_privates()を削除した際に、変更を元に戻すための関数です。
 * 元に戻した後はこちらも削除しましょう。
 */
/*
add_action( 'admin_init', function() {

  // 現在のユーザーの権限が著者である場合
  if( current_user_can( 'author' )
  && ( current_user_can( 'read_private_posts' ) || current_user_can( 'read_private_pages' ) ) ) {

    // ユーザーの権限オブジェクト取得
    $author = get_role( 'author' );

    // 非公開の投稿を表示する権限を削除
    $author->remove_cap( 'read_private_posts' );

    // 非公開の固定ページを表示する権限を削除
    $author->remove_cap( 'read_private_pages' );

  }

});
/** コードの上端にあるコメントアウトを消すことで使用できます。 */


/**
 * 投稿者の固定ページ下書き作成権限削除
 * 
 * yadoken_allow_author_edit_and_delete_pages()を削除した際に、変更を元に戻すための関数です。
 * 元に戻した後はこちらも削除しましょう。
 */
/*
add_action( 'admin_init', function() {
  
  // 現在のユーザーの権限が著者である場合
  if( current_user_can( 'author' )
  && ( current_user_can( 'edit_pages' ) || current_user_can( 'delete_pages' ) ) ) {

    // ユーザーの権限オブジェクト取得
    $author = get_role( 'author' );

    // 固定ページを編集する権限を削除
    $author->remove_cap( 'edit_pages' );

    // 固定ページを削除する権限を削除
    $author->remove_cap( 'delete_pages' );

  }

});
/** コードの上端にあるコメントアウトを消すことで使用できます。 */

?>