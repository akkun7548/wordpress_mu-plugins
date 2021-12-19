<?php
/*
Plugin Name: カスタム投稿タイプ「お知らせ」追加・編集プラグイン
Description: お知らせに関係する設定を行っています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * お知らせ
 * 
 * カスタム投稿タイプの追加のみを行っています。
 * 
 * - init
 *  - カスタム投稿タイプの登録
 */


/**
 * カスタム投稿タイプ「お知らせ」追加
 * 
 * ここでは、当該の投稿タイプを編集できる人の権限や、パーマリンクの構造などが設定できます。
 * 
 * お知らせの編集権限は固定ページと同一に設定されています。
 */
add_action( 'init', function() {

  // 登録
  register_post_type(
    'yadoken_news',
    array(
      'labels' => array(
        'name' => 'お知らせ'
      ),
      'description' => 'お知らせ用のカスタム投稿タイプです。',
      'public' => true,
      'exclude_from_search' => true,
      'menu_position' => 10,
      'menu_icon' => 'dashicons-megaphone',
      'capability_type' => 'page',
      'map_meta_cap' => true,
      'rewrite' => array(
        'slug' => 'news',
        'with_front' => false
      ),
      'show_in_rest' => true,
      'has_archive' => true
    )
  );

});

?>