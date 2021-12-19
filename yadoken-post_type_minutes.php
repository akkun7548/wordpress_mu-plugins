<?php
/*
Plugin Name: カスタム投稿タイプ「議事録」追加・編集プラグイン
Description: 議事録に関係する設定を行っています。
Version: 0.2
Author: 野生動物研究会
Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * 議事録
 * 
 * 投稿ステータス「非公開」動作変更プラグイン に依存しています。
 * 
 * - init
 *  - カスタム投稿タイプ「議事録」の登録
 * 
 * - pre_get_posts
 *  - 非ログイン時の議事録へのクエリを投稿タイプ'post'に書き換え
 * 
 * - template_redirect
 *  - 非ログイン時の議事録アーカイブへのクエリを404にリダイレクト
 * 
 * - publish_yadoken_minutes
 *  - 全ての議事録の投稿ステータスを公開時に非公開に上書き
 * 
 * - wp_nav_menu_objects
 *  - 非公開記事のメニューを表示権限を持つユーザーのみに表示
 * 
 * - widget_display_callback
 *  - 権限がない場合に議事録のウィジェットを出力しない
 * 
 * - private_title_format
 *  - 議事録の非公開記事のタイトルに「非公開：」を表示しない
 */


/**
 * カスタム投稿タイプ「議事録」追加
 * 
 * ここでは、当該の投稿タイプを編集できる人の権限や、パーマリンクの構造などが設定できます。
 */
add_action( 'init', function() {

  // 登録
  register_post_type(
    'yadoken_minutes',
    array(
      'labels' => array(
        'name' => '議事録'
      ),
      'description' => '内部向け議事録用のカスタム投稿タイプです。',
      'public' => true,
      'exclude_from_search' => true,
      'menu_position' => 20,
      'menu_icon' => 'dashicons-media-text',
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'rewrite' => array(
        'slug' => 'minutes',
        'with_front' => false
      ),
      'show_in_rest' => true,
      'has_archive' => true,
    )
  );

});


/**
 * 非ログイン時の投稿タイプ「議事録」へのクエリを投稿タイプ'post'に書き換え
 * 
 * ログインしていない状態で議事録アーカイブにアクセスした場合、'post'が取得されるように
 * 設定しています。
 * 
 * 全記事用アーカイブ以外のアーカイブでは、存在しない投稿タイプが指定された時'post'として返される
 * 仕様になっているため、それに合わせて権限がない時は投稿タイプを'post'に変更するようにしています。
 * 
 * @param WP_Query $this
 */
add_action( 'pre_get_posts', function( $query ) {

  /**
   * 管理ページ、メインループ以外のクエリを対象外にしています。
   * また、個別ページの場合を対象にした変更は行っていないため個別ページも除外しています。
   */
  if( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
    return;
  }

  // 投稿タイプ取得
  $post_type = $query->get( 'post_type', '' );

  /**
   * 議事録を表示する権限の有無
   * 
   * WP_Post_Type->cap->read_private_postsはプロパティ名です。このプロパティの値として
   * 'read_private_posts', 'read_private_pages'の二つの権限がデフォルトで登録されています。
   * プロパティ名と値を混同しないように気を付けてください。
   * 
   * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
   */
  $read_private_posts = current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts );

  // 議事録がクエリされていて、表示権限がない場合
  if( $post_type === 'yadoken_minutes' && ! $read_private_posts ) {

    /**
     * 全記事用アーカイブは権限がない時に フック"template_redirect"で404ページになるように
     * しているため、その他のアーカイブを対象としています。
     */
    if( $query->is_search() || $query->is_date()
    || $query->is_author() || $query->is_tax() ) {

      // 投稿タイプを'post'に上書き
      $query->set( 'post_type', 'post' );
    }

  }

  // 複数の投稿タイプがクエリされていて、議事録の表示権限がない場合
  if( is_array( $post_type ) && ! $read_private_posts ) {

    foreach( $post_type as $key => $value ) {

      // 議事録が含まれている場合は削除
      if( $value === 'yadoken_minutes' ) {
        unset( $post_type[$key] );
      }

    }

    // 議事録のみの配列だった場合、要素がなくなる
    if( count( $post_type ) === 0 ) {

      // 投稿タイプを'post'に上書き
      $post_type = 'post';
    }

    // 投稿タイプを上書き
    $query->set( 'post_type', $post_type );

  }
  
});


/**
 * 非ログイン時の議事録アーカイブへのクエリを404にリダイレクト
 * 
 * 議事録のアーカイブページに非ログインユーザーからアクセスがあった場合に404エラーを
 * 返すように設定しています。
 * 
 * 'pre_get_posts'フックの方が先に動作するため、こちらに分岐するのは一覧アーカイブのみとなります。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名です。このプロパティの値として
 * 'read_private_posts', 'read_private_pages'の二つの権限がデフォルトで登録されています。
 * プロパティ名と値を混同しないように気を付けてください。
 * 
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 */
add_action( 'template_redirect', function() {

  // 議事録を表示する権限がない、もしくはログインしていない状態で議事録をクエリした場合
  if( is_post_type_archive( 'yadoken_minutes' )
  && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
    global $wp_query;

    // クエリを404に上書き
    $wp_query->set_404();

    // HTTPヘッダのステータスを404に変更
    status_header( 404 );

    // ブラウザのキャッシュを無効化するHTTPヘッダを出力
    nocache_headers();
  }

});


/**
 * 全ての議事録の投稿ステータスを公開時に非公開に上書き
 * 
 * フィルター名は{$new_status}_{$post->post_type}です。
 * 
 * @param int $post_id   投稿ID
 * @param WP_Post $post  投稿オブジェクト
 */
add_action( 'publish_yadoken_minutes', function( $post_id, $post ) {

  // 投稿ステータスを上書き
  $post->post_status = 'private';

  // 変更した投稿オブジェクトを反映
  wp_update_post( $post );

}, 100, 2 );


/**
 * 議事録一覧アーカイブのメニュー項目を表示権限を持つユーザーのみに表示
 * 
 * 議事録一覧アーカイブにリンクするメニューアイテムを権限がない場合に配列から消去しています。
 * 
 * 親項目が削除された場合はその状態に関わらず子項目も削除されます。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名です。このプロパティの値として
 * 'read_private_posts', 'read_private_pages'の二つの権限がデフォルトで登録されています。
 * プロパティ名と値を混同しないように気を付けてください。
 * 
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

    // 取得した投稿タイプアーカイブオブジェクトが議事録で、現在のユーザーがその表示権限を持っていない場合
    if( $item->object === 'yadoken_minutes'
    && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {

      // 配列から当該メニューオブジェクトを削除
      unset( $sorted_menu_items[$key] );

      // 削除した項目のID配列に追加
      $unset_ids[] = $item->ID;

      // この時点で配列から取り除かれるため、foreach内の残りの処理をスキップしています。
      continue;

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
 * 権限がない場合に議事録のウィジェットを出力しない
 * 
 * @param array     $instance  現在のインスタンスの設定
 * @param WP_Widget $this      現在のインスタンス
 * @param array     $args      ウィジェットのデフォルト引数(不使用)
 * @return array|false 現在のインスタンスの設定
 */
add_filter( 'widget_display_callback', function( $instance, $widget ) {

  /**
   * ウィジェット上書き・拡張プラグインで定義しているウィジェットについて、
   * 議事録が設定されていて、かつその表示権限がない場合
   */
  $is_yadoken_widget = $widget instanceof Yadoken_WP_Widget_Recent_Posts
                       || $widget instanceof Yadoken_WP_Widget_Archives;
  if( $is_yadoken_widget 
      && isset( $instance['post_type'] )
      && $instance['post_type'] === 'yadoken_minutes'
      && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
        
    return false;
  }

  return $instance;

}, 10, 2 );


/**
 * 議事録のタイトルに”非公開”を表示しないようにする
 * 
 * @param string $prepend  タイトルのフォーマット
 * @param WP_Post $post    現在の投稿オブジェクト
 */
add_filter( 'private_title_format', function( $prepend, $post ) {

  // 現在の投稿が議事録であった場合
  if( $post->post_type === 'yadoken_minutes' ) {

    // フォーマットから「非公開：」を削除
    $prepend = '%s';
  }

  return $prepend;

}, 10, 2 );

?>