<?php
/*
 Plugin Name: ウィジェット機能拡張プラグイン
 Description: 全てのウィジェットの機能を拡張しています。
 Version: 0.2
 Author: 野生動物研究会
 Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * ウィジェットの機能拡張
 * 
 * - 全てのウィジェットに以下の機能を追加
 *  - 選択したページのみで表示する。
 *  - ログインユーザーのみに表示する。
 * 
 *  - in_widget_form
 *   - ウィジェット追加画面に拡張機能の項目を出力
 * 
 *  - widget_update_callback
 *   - ウィジェット拡張機能の設定を保存
 * 
 *  - widget_display_callback
 *   - ウィジェット表示に拡張機能を反映
 */


/**
 * 全ウィジェットに表示場所の選択機能を追加(入力フォーム)
 * 
 * 全てのウィジェットの下部にログインユーザー向けのチェックボックス、全ページで表示するかの
 * チェックボックス、表示する各ページのチェックボックスを追加しています。
 * 
 * 全ページで表示するのチェックボックスを有効にしている状態では、各ページのチェックボックスは
 * 無効化されるため出力されないようにしました。
 * 
 * @param WP_Widget $this      ウィジェットのインスタンス(参照渡し)
 * @param null      $return    $this->form()がreturnした値：通常は何も返さない
 * @param array     $instance  ウィジェットの設定
 */
add_action( 'in_widget_form', function( $widget, $return, $instance ) {

  // 内部向けウィジェット
  $internal = isset( $instance['yadoken_internal'] ) ? (bool) $instance['yadoken_internal'] : false;
  ?>
  <p>
  <input class="checkbox" type="checkbox"<?php checked( $internal ); ?> id="<?php echo $widget->get_field_id( 'yadoken_internal' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_internal' ); ?>" />
  <label for="<?php echo $widget->get_field_id( 'yadoken_internal' ); ?>"><?php echo 'ログインユーザー向け'; ?></label>
  </p>
  <?php

  //セットされていない場合の初期値としてtrue、つまり全ページで表示するように設定しています。
  $all_display = isset( $instance['yadoken_all_display'] ) ? (bool) $instance['yadoken_all_display'] : true;
  ?>
  <p>
  <input class="checkbox" type="checkbox"<?php checked( $all_display ); ?> id="<?php echo $widget->get_field_id( 'yadoken_all_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_all_display' ); ?>" />
  <label for="<?php echo $widget->get_field_id( 'yadoken_all_display' ); ?>"><?php echo '全ページで表示'; ?></label>
  </p>
  <?php
  if( ! $all_display ) {

    // 固定ページの投稿ステータス
    $args = array( 'post_status' => 'publish,private' );

    // 公開・非公開の固定ページを取得
    if( $pages = get_pages( $args ) ) {
      
      //管理画面にはstyle.cssが読み込まれないため、属性値としてスタイリングしました。
      ?>
      <div class="yadoken-widget_form">
        <label>固定ページ:</label>
        <p>
        <?php
        // 各固定ページのループ
        foreach( (array) $pages as $page ) {

          $key = $page->ID;

          // 投稿ページは投稿タイプ”投稿”に紐づくため飛ばす
          if( $key === (int) get_option( 'page_for_posts' ) ) {
            continue;
          }

          // 現在の設定を取得
          $page_display[$key] = ( isset( $instance['yadoken_page_display'] ) && in_array( $key, array_map( 'intval', $instance['yadoken_page_display'] ), true ) ) ? true : false;
          ?>
          <span>
            <input class="checkbox" type="checkbox"<?php checked( $page_display[$key] ); ?> id="<?php echo $widget->get_field_id( 'yadoken_page_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_page_display' ) . '[]'; ?>" value="<?php echo esc_attr( $key ); ?>" />
            <label for="<?php echo $widget->get_field_id( 'yadoken_page_display' ); ?>"><?php echo esc_html( $page->post_title ); ?></label>
          </span>
          <?php
        } ?>
        </p>
      </div>
      <?php
    }

    // 追加投稿タイプと”投稿”の管理名を取得
    $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
    ?>
    <div class="yadoken-widget_form">
      <label>投稿タイプ:</label>
      <p>
      <?php
      // 各投稿タイプのループ
      foreach( $post_types as $key ) {

        // 現在の設定の取得
        $post_type_display[$key] = ( isset( $instance['yadoken_post_type_display'] ) && in_array( $key, $instance['yadoken_post_type_display'], true ) ) ? true : false;
        ?>
        <span>
          <input class="checkbox" type="checkbox"<?php checked( $post_type_display[$key] ); ?> id="<?php echo $widget->get_field_id( 'yadoken_post_type_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_post_type_display' ) . '[]'; ?>" value="<?php echo esc_attr( $key ); ?>" />
          <label for="<?php echo $widget->get_field_id( 'yadoken_post_type_display' ); ?>"><?php echo ( $obj = get_post_type_object( $key ) ) ? $obj->labels->name : '' ; ?></label>
        </span>
        <?php
      } ?>
      </p>
    </div>
    <?php
  }

}, 10, 3 );


/**
 * 全ウィジェットに表示場所の選択機能を追加(保存)
 * 
 * フォームからPOST送信されてくる配列の要素はチェックボックスがオンになっていたもののみです。
 * 
 * @param array     $instance      現在のインスタンスの設定値
 * @param array     $new_instance  設定画面からの入力値の連想配列
 * @param array     $old_instance  同じインスタンスの更新前の設定
 * @param WP_Widget $this          現在のインスタンス
 * @return array 保存する更新後の設定の連想配列
 */

add_filter( 'widget_update_callback', function( $instance, $new_instance ) {
  
  // 内部向けウィジェット
  $instance['yadoken_internal'] = isset( $new_instance['yadoken_internal'] ) ? (bool) $new_instance['yadoken_internal'] : false;

  // 全ページで出力
  $instance['yadoken_all_display'] = isset( $new_instance['yadoken_all_display'] ) ? (bool) $new_instance['yadoken_all_display'] : false;

  // ウィジェットを出力する固定ページ
  $instance['yadoken_page_display'] = array();
  if( isset( $new_instance['yadoken_page_display'] ) ) {
    foreach( (array) $new_instance['yadoken_page_display'] as $value ) {
      $instance['yadoken_page_display'][] = $value;
    }
  }

  // ウィジェットを出力する投稿タイプ
  $instance['yadoken_post_type_display'] = array();
  if( isset( $new_instance['yadoken_post_type_display'] ) ) {
    foreach( (array) $new_instance['yadoken_post_type_display'] as $value ) {
      $instance['yadoken_post_type_display'][] = $value;
    }
  }

  return $instance;

}, 10, 2 );


/**
 * 全ウィジェットに表示場所の選択機能を追加(出力)
 * 
 * 固定ページの場合は配列に当該ページのID、その他のページの場合は投稿タイプスラッグが
 * 含まれているかで、表示するかを判定しています。
 * 
 * $instanceをfalseにすることで当該ウィジェットが表示されなくなります。
 * 
 * @param array     $instance  現在のインスタンスの設定
 * @param WP_Widget $this      現在のインスタンス
 * @param array     $args      ウィジェットのデフォルト引数
 * @return array falseで表示しない
 */
add_filter( 'widget_display_callback', function( $instance, $widget, $args ) {

  // 内部向けページかつユーザーがログインしていなかった場合
  if( isset( $instance['yadoken_internal'] ) && $instance['yadoken_internal'] && ! is_user_logged_in() ) {
    $instance = false;

  // 全ページで出力する設定ではない場合
  } elseif( isset( $instance['yadoken_all_display'] ) && ! $instance['yadoken_all_display'] ) {

    // 固定ページ
    if( is_page() ) {
      if( isset( $instance['yadoken_page_display'] ) ) {
        if( ! in_array( get_the_ID(), array_map( 'intval', $instance['yadoken_page_display'] ), true ) ) {
          $instance = false;
        }
      } else {
        $instance = false;
      }

    // 固定ページ以外
    } else {
      if( isset( $instance['yadoken_post_type_display'] ) ) {
        $post_type = ( $post_type =  get_query_var( 'post_type' ) ) ? (array) $post_type : array( 'post' );
        if( ! array_intersect( $post_type , $instance['yadoken_post_type_display'] ) ) {
          $instance = false;
        }
      } else {
       $instance = false;
      }
    }
  }
  
  return $instance;

}, 10, 3 );


/**
 * widgets.phpにインラインスタイルを出力
 * 
 * ウィジェットの設定画面にcssを出力しています。
 * 
 * @param string $hook_suffix  現在の管理画面
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {

  // 現在の管理画面がwidgets.phpの場合
  if( $hook_suffix === 'widgets.php' ) {

    // 出力するcss
    $css = <<< EOF
    .yadoken-widget_form {
      margin: 1em 0;
    }
    .yadoken-widget_form p {
      border: 2px solid #eeeeee;
      border-radius: 3px;
      padding: 5px;
      margin: 0;
    }
    .yadoken-widget_form span {
      margin: 0 3px;
      display: inline-block;
    }
    EOF;

    /**
     * インラインスタイルを追加
     * 
     * 第一引数のハンドル名のスタイルシートが読み込まれている時、第二引数の文字列がインラインスタイル
     * として出力されます。
     */
    wp_add_inline_style( 'wp-admin', $css );

  }

});

?>