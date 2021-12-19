<?php
/*
Plugin Name: 投稿ステータス「非公開」動作変更プラグイン
Description: 非公開記事を内部共有用途に用いるための変更を行っています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * 非公開記事を内部共有用途に用いるための変更
 * 
 * 投稿者以上の権限でログイン時のみに閲覧できる投稿の状態を定義しています。
 * 
 * - nav_menu_meta_box_object
 *  - メニューページで選択肢に非公開記事を含める
 * 
 * - wp_nav_menu_objects
 *  - 非公開記事のメニューを表示権限を持つユーザーのみに表示
 * 
 * - widget_posts_args
 *  - 表示権限がある場合に、ウィジェット「最近の投稿」で非公開記事を表示
 * 
 * - getarchives_where
 *  - wp_get_archives()で権限がある場合に非公開の記事を取得
 */


/**
 * メニューページで選択肢に非公開記事を含める
 * 
 * 外観 > メニュー >メニュー項目を追加 の下のアコーディオンの中に表示される記事に
 * 非公開の記事が含まれるようにしました。
 * 
 * 参照：https://developer.wordpress.org/reference/functions/wp_nav_menu_item_post_type_meta_box/
 * "_default_query" はWP_Query->query()の引数の一部となります。
 * 
 * @param WP_Post_Type|WP_Taxonomy|false $post_type  投稿タイプオブジェクト
 * @return WP_Post_Type|WP_Taxonomy|false
 */
add_filter( 'nav_menu_meta_box_object', function( $post_type ) {

  // 投稿タイプオブジェクトである場合
  if( $post_type instanceof WP_Post_Type
  && current_user_can( get_post_type_object( $post_type->name )->cap->read_private_posts ) ) {

    // 投稿ステータスをカンマ区切りで複数指定
    $post_type->_default_query['post_status'] = 'publish,private';

  }

  return $post_type;

});


/**
 * 内部ページのメニューをログインユーザーのみに表示
 * 
 * 投稿の状態がprivate(非公開)だった場合、その投稿にリンクしたメニューアイテムを配列から消去しています。
 * 
 * 親項目が削除された場合はその状態に関わらず子項目も削除されます。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名のため、同名の権限と混同しないように
 * 気を付けてください。
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 * 
 * @param array    $sorted_menu_items  ソート済みのメニューオブジェクトの配列
 * @param stdClass $args               wp_nav_menu()の引数オブジェクト(不使用)
 * @return array  ソート済みのメニューオブジェクトの配列
 */
add_filter( 'wp_nav_menu_objects', function( $sorted_menu_items ) {

  // 削除した項目のIDの配列
  $unset_ids = array();

  // メニューオブジェクトの配列を展開
  foreach( $sorted_menu_items as $key => $item ) {

    // 個別ページ
    if( $item->type === 'post_type' ) {

      // メニューオブジェクトと関連付けられた投稿オブジェクトを取得
      $post = get_post( $item->object_id );

      // 取得した投稿のステータスが非公開で、現在のユーザーが非公開投稿の表示権限を持っていない場合
      if( $post instanceof WP_Post && $post->post_status === 'private'
      && ! current_user_can( get_post_type_object( $post->post_type )->cap->read_private_posts ) ) {

        // 配列から当該メニューオブジェクトを削除
        unset( $sorted_menu_items[$key] );

        // 削除した項目のID配列に追加
        $unset_ids[] = $item->ID;

        // この時点で配列から取り除かれるため、foreach内の残りの処理をスキップしています。
        continue;

      }

    }

    // 親項目が削除されていた場合、その子項目も削除
    if( in_array( $item->menu_item_parent, $unset_ids ) ) {

      // 配列から当該メニューオブジェクトを削除
      unset( $sorted_menu_items[$key] );

      // 削除した項目のID配列に追加
      $unset_ids[] = $item->ID;

    }

  }

  return $sorted_menu_items;

});


/**
 * 「最近の投稿」ウィジェットで権限保有時に非公開の記事を取得
 * 
 * Yadoken_WP_Widget_Recent_Postsの定義の有無に関わらず、非公開記事の取得する権限を
 * 投稿タイプオブジェクトから判断しています。
 * 
 * Yadoken_WP_Widget_Recent_Postsが定義されている場合、post_typeが設定された後に
 * このコールバックを適用するため、優先度を下げています。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名です。このプロパティの値として
 * 'read_private_posts', 'read_private_pages'の二つの権限がデフォルトで登録されています。
 * プロパティ名と値を混同しないように気を付けてください。
 * 
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 * 
 * @param array $args      投稿を取得する引数
 * @param array $instance  ウィジェットのインスタンス変数(不使用)
 * @return array  投稿を取得する引数
 */
add_filter( 'widget_posts_args', function( $args ) {

  // 投稿タイプが設定されている場合はその設定値、されていない場合は'post'を使用
  $post_type = isset( $args['post_type'] ) ? $args['post_type'] : 'post';

  // 投稿オブジェクトを取得
  $obj = get_post_type_object( $post_type );

  // 現在のユーザーの表示権限を確認
  if( $obj instanceof WP_Post_Type
  && current_user_can( $obj->cap->read_private_posts ) ) {

    // 投稿ステータスをカンマ区切りで複数指定
    $args['post_status'] = 'publish,private';

  }

  return $args;

}, 100 );


/**
 * wp_get_archives()で権限がある場合に非公開の記事を取得
 * 
 * 「アーカイブ」ウィジェットではwp_get_archives()でアーカイブリンクのリストを取得しているため、
 * その内部のgetarchives_whereフックで動作を変更しています。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名です。このプロパティの値として
 * 'read_private_posts', 'read_private_pages'の二つの権限がデフォルトで登録されています。
 * プロパティ名と値を混同しないように気を付けてください。
 * 
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 * 
 * @param string $sql_where   SQLクエリ
 * @param array $parsed_args  デフォルト引数
 * @return string SQLクエリ
 */
add_filter( 'getarchives_where', function( $sql_where, $parsed_args ) {

  // 投稿タイプオブジェクトを取得
  $obj = get_post_type_object( $parsed_args['post_type'] );

  // 現在のユーザーの表示権限を確認
  if( $obj instanceof WP_Post_Type
  && current_user_can( $obj->cap->read_private_posts ) ) {

    // SQL文のWHERE clauseの投稿ステータスを上書き
    $sql_where = str_replace( "post_status = 'publish'", "post_status IN ( 'publish', 'private' )" , $sql_where );

  }

  return $sql_where;

}, 10, 2 );

?>