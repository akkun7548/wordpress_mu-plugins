<?php
/*
Plugin Name: ユーザー編集プラグイン
Description: ユーザー関係の動作を変更しています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * ユーザー権限を各アカウントの使用目的に最適化
 * 
 * - publish_post
 *  - 権限が投稿者のアカウントで公開するpostの投稿ステータスを非公開に上書き
 * 
 * - admin_init
 *  - 権限が投稿者のアカウントに非公開の記事を表示する権限を追加
 * 
 * - admin_init
 *  - 権限が投稿者のアカウントに固定ページの下書きを作成する権限を追加
 * 
 * - manage_pages_columns/yadoken_manage_posts_columns
 * - manage_posts_columns/yadoken_manage_posts_columns
 *  - 管理画面edit.phpの投稿リストの列を編集
 */

 
/**
 * 投稿者が公開する記事を全て非公開にする。
 * 
 * フィルター名は{$new_status}_{$post->post_type}です。
 * 
 * @param int $post_id   投稿ID
 * @param WP_Post $post  投稿オブジェクト
 */
add_action( 'publish_post', function( $ID, $post ) {

  // 現在のユーザーの権限が投稿者の場合
  if( current_user_can( 'author' ) ) {

    // 投稿ステータスを上書き
    $post->post_status = 'private';

    // 変更した投稿オブジェクトを反映
    wp_update_post( $post );

  }

}, 10, 2 );


/**
 * 投稿者の非公開記事表示権限追加
 * 
 * get_role()はWP_Roles()->get_role()のラッパーであり、WP_Roleオブジェクトを返します。
 * WP_Role()->add_cap()は内部でWP_Roles()->add_cap()を呼び出しています。
 * このメソッドではデータベースに値を保存しているため、このフックを削除しても
 * 変更は保存され続けます。
 */
add_action( 'admin_init', function() {

  // 現在のユーザーの権限が著者である場合
  if( current_user_can( 'author' )
  && ( ! current_user_can( 'read_private_posts' ) || ! current_user_can( 'read_private_pages' ) ) ) {

    // ユーザーの権限オブジェクト取得
    $author = get_role( 'author' );

    // 非公開の投稿を表示する権限を追加
    $author->add_cap( 'read_private_posts' );

    // 非公開の固定ページを表示する権限を追加
    $author->add_cap( 'read_private_pages' );

  }

});


/**
 * 投稿者の固定ページ下書き作成許可
 * 
 * get_role()はWP_Roles()->get_role()のラッパーであり、WP_Roleオブジェクトを返します。
 * WP_Role()->add_cap()は内部でWP_Roles()->add_cap()を呼び出しています。
 * このメソッドではデータベースに値を保存しているため、このフックを削除しても
 * 変更は保存され続けます。
 */
add_action( 'admin_init', function() {

  // 現在のユーザーの権限が著者である場合
  if( current_user_can( 'author' )
  && ( ! current_user_can( 'edit_pages' ) || ! current_user_can( 'delete_pages' ) ) ) {

    // ユーザーの権限オブジェクト取得
    $author = get_role( 'author' );

    // 固定ページを編集する権限を追加
    $author->add_cap( 'edit_pages' );

    // 固定ページを削除する権限を追加
    $author->add_cap( 'delete_pages' );

  }

});


/**
 * 管理画面edit.phpの投稿リストの列を編集
 * 
 * @param string[] $post_columns  列のスラッグと名前の連想配列
 * 
 * @param string[] $post_columns  列のスラッグと名前の連想配列
 * @param string $post_type       投稿タイプスラッグ
 * 
 * @return string[]  列のスラッグと名前の連想配列
 */
add_filter( 'manage_pages_columns', 'yadoken_manage_posts_columns' );
add_filter( 'manage_posts_columns', 'yadoken_manage_posts_columns' );
function yadoken_manage_posts_columns( $post_columns ) {
  
  // 現在のユーザーが管理者、編集者以外である場合
  if( ! current_user_can( 'administrator' ) && ! current_user_can( 'editor' ) ) {

    // 投稿者名(=アカウント名)を削除
    if( isset( $post_columns['author'] ) ) {
      unset( $post_columns['author'] );
    }

    // コメントを削除
    if( isset( $post_columns['comments'] ) ) {
      unset( $post_columns['comments'] );
    }    

  }

  return $post_columns;

}

?>