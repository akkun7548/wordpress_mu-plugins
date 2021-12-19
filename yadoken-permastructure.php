<?php
/*
Plugin Name: パーマリンク構造変更プラグイン
Description: パーマリンク構造の変更と、それに伴う対応を行っています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * パーマリンク構造の変更とその対応
 * 
 * 投稿タイプ'post'の個別ページ、カテゴリーアーカイブ、タグアーカイブのurlの先頭に、
 * 設定 > 表示設定 > ホームページの表示 > 固定ページ > 投稿ページ で設定したページの
 * スラッグが付加されるように設定しています。
 * 
 * - init
 *  - {$taxomony}_rewrite_rules/yadoken_change_rewrite_rules
 * - post_rewrite_rules/yadoken_change_rewrite_rules
 *   - postとそれに関係するタクソノミーのリライトルールに投稿ページのスラッグを挿入
 * 
 * - update_option_page_for_posts
 *  - 投稿ページ変更時にリライトルール更新
 * 
 * - save_post_page
 *  - 投稿ページ更新時にリライトルール更新
 * 
 * - pre_post_link/yadoken_pre_post_link
 *  - 「投稿」個別ページのリンクに投稿ページのスラッグを挿入
 * 
 * - pre_term_link
 *  - 「投稿」に関数するタクソノミーに属するタームのリンクに投稿ページのスラッグを挿入
 */

 
/**
 * パーマリンク構造は「基本」に設定されているとき空文字列となり、リライトルールが生成されません。
 * それ以外に設定されている場合に、リライトルールの変更を行っています。
 */
if( get_option( 'permalink_structure' ) ) {


  /**
   * postに関係するページのリライトルールの先頭に投稿ページのスラッグを挿入
   * 
   * mu-pluginsが読み込まれる時点ではタクソノミーが登録されていないため、
   * get_object_taxonomies()ではタクソノミーを取得出来ません。
   * このため、initフックを利用して"{$permastructname}_rewrite_rules"の実行タイミングを遅くしています。
   * 
   * リライトルールの配列は 書き換え前 => 書き換え後 という構造になっています。
   * 
   * @param array $rewrite  リライトルール
   * @return array  新しいリライトルール
   */
  add_action( 'init', function() {

    // 投稿に紐付けられた全てのタクソノミーのフックに下記の関数を登録
    foreach( get_object_taxonomies( 'post' ) as $taxonomy ) {
      add_filter( "{$taxonomy}_rewrite_rules", 'yadoken_change_rewrite_rules' );
    }

  }, 20 );
  add_filter( 'post_rewrite_rules', 'yadoken_change_rewrite_rules', 20 );
  function yadoken_change_rewrite_rules( $rewrite ) {

    // 投稿ページが設定されている場合
    if( $page_for_posts = get_post( get_option( 'page_for_posts' ) ) ) {

      // 新しいリライトルール
      $new_rewrite = array();

      // 投稿ページのスラッグ
      $post_name = $page_for_posts->post_name;

      // 全てのリライトルールのキー(パス文字列)を変更
      foreach( $rewrite as $key => $value ) {
        $new_rewrite[$post_name . '/' . $key] = $value;
      }

      // リライトルールを上書き
      $rewrite = $new_rewrite;
    }

    return $rewrite;

  }


  /**
   * 投稿ページを変更した時にパーマリンクを更新
   * 
   * フック名：update_option_{$option}
   * 
   * パーマリンク構造の変更は、設定 > パーマリンク設定 > 変更を保存 を押下することで反映されます。
   * 具体的には$wp_rewrite->flush_rules( false )の実行が必要となります。
   * yadoken_change_rewrite_rulesでは投稿ページのスラッグを利用しているため、その変更時に
   * リライトルールの更新を行っています。
   * 
   * @param mixed $old_value  更新前の値(不使用)
   * @param mixed $value      更新後の値(不使用)
   * @param string $option    オプション名(不使用)
   */
  add_action( 'update_option_page_for_posts', function() {
    global $wp_rewrite;

    // リライトルールを再生成
    $wp_rewrite->flush_rules( false );

  }, 20 );


  /**
   * 投稿ページを更新した時にパーマリンクを更新する。
   * 
   * フック名：save_post_{$post->post_type}
   * 
   * パーマリンク構造の変更は、設定 > パーマリンク設定 > 変更を保存 を押下することで反映されます。
   * 具体的には$wp_rewrite->flush_rules( false )の実行が必要となります。
   * yadoken_change_rewrite_rulesでは投稿ページのスラッグを利用しているため、その変更時に
   * リライトルールの更新を行っています。
   * 
   * @param int $post_ID   投稿ID
   * @param WP_Post $post  投稿オブジェクト(不使用)
   * @param bool $update   更新された投稿かどうか(不使用)
   */
  add_action( 'save_post_page', function( $post_ID ) {

    // 更新された投稿が投稿ページだった場合
    if( $post_ID === (int) get_option( 'page_for_posts' ) ) {
      global $wp_rewrite;

      // リライトルールを再生成
      $wp_rewrite->flush_rules( false );

    }

  }, 20 );
  

  /**
   * リライトルールの変更に「投稿」個別ページへのリンクを対応させる。
   * 
   * リンクの取得はリライトルールの生成とは独立して行われるため、こちらにも同様の変更を
   * 加える必要があります。
   * 
   * @param string $permalink  パーマリンク構造
   * @param WP_Post $post    投稿オブジェクト(不使用)
   * @param bool $leavename  投稿名を残すかどうか(不使用)
   * @return string  新しいリンク
   */
  add_filter( 'pre_post_link', 'yadoken_pre_post_link', 20 );
  function yadoken_pre_post_link( $link ) {

    // リライトルールの先頭部分
    $base = '';

    // 投稿ページが設定されている場合
    if( $page_for_posts = get_post( get_option( 'page_for_posts' ) ) ) {

      // 投稿ページのスラッグを挿入
      $base = '/' . $page_for_posts->post_name;
    }

    return $base . $link;
    
  }

  
  /**
   * リライトルールの変更に「投稿」に関連したタクソノミーのリンクを対応させる。
   * 
   * リンクの取得はリライトルールの生成とは独立して行われるため、こちらにも同様の変更を
   * 加える必要があります。
   * 
   * @param string $termlink  タクソノミーのパーマリンク構造
   * @param WP_Term $term  タームオブジェクト(不使用)
   * @return string  新しいリンク
   */
  add_filter( 'pre_term_link', function( $termlink, $term ) {

    // 「投稿」に関係するタクソノミーに属するタームである場合
    if( $term instanceof WP_Term
    && in_array( $term->taxonomy, get_object_taxonomies( 'post' ), true ) ) {

      // 変更内容は共通であるため、上記関数を使用
      $termlink = yadoken_pre_post_link( $termlink );
    }

    return $termlink;

  }, 20, 2 );

} // if( get_option( 'permalink_structure' ) )

?>