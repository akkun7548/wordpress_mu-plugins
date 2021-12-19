<?php
/*
Plugin Name: 外見変更プラグイン
Description: wordpressデフォルトの設定を上書きする形で外見を編集しています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * 外見
 * 
 * 外見を変更しています。
 * 
 * - post_type_labels_post
 *  - デフォルト投稿タイプ「投稿」の名前を「活用報告」に変更
 */


/**
 * 「投稿」を「活動報告」に変更
 * 
 * wordpressデフォルトの投稿タイプであるpostのネームラベルを、「投稿」から「活動報告」に
 * 変更しています。
 * 
 * フィルター名はpost_type_labels_{$post_type}です。
 * 
 * @param object $labels  投稿タイプネームラベル
 * @return object
 */
add_filter( 'post_type_labels_post', function( $labels ) {
  
  // ネームラベルオブジェクトの「投稿」を「活用報告」に書き換え
  foreach ( $labels as $key => $value ) {
    $labels->$key = str_replace( '投稿', '活動報告', $value );
  }

  return $labels;

});

?>