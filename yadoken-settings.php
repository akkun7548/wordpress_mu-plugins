<?php
/**
 * Wordpress設定
 * 
 * テーマを切り替えた時も残す必要がある設定をこちらにまとめました。
 * 
 * 主に以下の設定を行っています。
 * ・カスタム投稿タイプの設定及び、postのネームラベル変更
 * ・カスタム投稿タイプ「議事録」を内部向けとするための設定
 * ・投稿者の権限の変更
 * ・パーマリンク構造の変更
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
add_filter( 'post_type_labels_post', 'yadoken_post_type_labels_post' );
function yadoken_post_type_labels_post( $labels ) {
  foreach ( $labels as $key => $value ) {
      $labels->$key = str_replace( '投稿', '活動報告', $value );
  }
  return $labels;
}

/**
 * カスタム投稿タイプ追加
 * 
 * カスタム投稿タイプを追加しています。
 * ここでは、当該の投稿タイプを編集できる人の権限や、パーマリンクの構造などが設定できます。
 */
add_action( 'init', 'yadoken_register_post_type' );
function yadoken_register_post_type() {

  /**
   * お知らせ
   * 
   * 編集権限は固定ページと同一に設定されています。
   */
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

  /**
   * 議事録
   * 
   * 非ログインユーザーに対して、投稿タイプアーカイブはyadoken_template_redirect()で404エラーに、
   * その他のアーカイブはyadoken_change_main_loop()でクエリをpostに書き換えています。
   * また、議事録はyadoken_deny_publish_minutes()によって状態がpublishになることはありません。
   */
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
}

/**
 * カスタム投稿タイプ名変更用
 * 
 * register_post_type()の第一引数を変更した際データベースのpost_typeフィールドは更新されないため、
 * Post_Type_Switcherなどのプラグインを使って投稿タイプを変更する必要があります。
 * その際に新旧投稿タイプが両方登録されている必要があるため、一時的にこのコードを使用しています。
 */
add_action( 'init', 'yadoken_register_post_type_old' );
function yadoken_register_post_type_old() {

  //お知らせ(旧)
  register_post_type(
    'news',
    array(
      'labels' => array(
        'name' => 'お知らせ(旧)'
      ),
      'description' => 'お知らせ用のカスタム投稿タイプです。',
      'public' => true,
      'exclude_from_search' => true,
      'menu_position' => 10,
      'menu_icon' => 'dashicons-megaphone',
      'capability_type' => 'page',
      'map_meta_cap' => true,
      'rewrite' => array(
        'slug' => 'news_old',
        'with_front' => false
      ),
      'show_in_rest' => true,
      'has_archive' => true,
    )
  );

  //議事録(旧)
  register_post_type(
    'minutes',
    array(
      'labels' => array(
        'name' => '議事録(旧)'
      ),
      'description' => '内部向け議事録のカスタム投稿タイプです。',
      'public' => true,
      'exclude_from_search' => true,
      'menu_position' => 20,
      'menu_icon' => 'dashicons-media-text',
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'rewrite' => array(
        'slug' => 'minutes_old',
        'with_front' => false
      ),
      'show_in_rest' => true,
      'has_archive' => true,
    )
  );
}

/**
 * 非ログイン状態で議事録アーカイブのクエリを活動報告に変換
 * 
 * ログインしていない状態で議事録アーカイブにアクセスした場合、活動報告が取得されるように
 * 設定しています。
 * 
 * @param WP_Query $this
 */
add_action( 'pre_get_posts', 'yadoken_change_minutes_query' );
function yadoken_change_minutes_query( $query ) {
  /**
   * 管理ページ、メインループ以外のクエリを対象外にしています。
   * また、個別ページの場合を対象にした変更は行っていないため個別ページも除外しています。
   */
  if( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
    return;
  }
  $post_type = $query->get( 'post_type' );
  /**
   * 議事録を表示する権限の有無を判定
   * 
   * 議事録アーカイブの表示は非公開の議事録を表示する権限を持つかで判断しています。
   * 
   * WP_Post_Type->cap->read_private_postsはプロパティ名のため、同名の権限と混同しないように
   * 気を付けてください。
   * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
   */
  if( in_array( 'yadoken_minutes', (array) $post_type, true ) && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
    //複数の投稿タイプがクエリされていた場合
    if( is_array( $post_type ) ) {
      foreach( $post_type as $key => $value ) {
        if( $value === 'yadoken_minutes' ) {
          unset( $post_type[$key] );
        }
      }
      $query->set( 'post_type', $post_type );
    /**
     * 議事録のみがクエリされていた場合
     * 
     * 全記事用アーカイブ以外のアーカイブでは、存在しない投稿タイプが指定された時postが返される
     * 仕様になっているため、それに合わせて権限がない時はpost_typeをpostに変更するようにしています。
     * 
     * また、全記事用アーカイブは権限がない時にyadoken_minutes_404()で404ページになるように
     * しているため、こちらでは対象外としています。
     */
    } elseif( $query->is_search() || $query->is_date() || $query->is_author() || $query->is_tax() ) {
      $query->set( 'post_type', 'post' );
    }
  }
}

/**
 * 非ログイン時議事録のアーカイブページで404を返す
 * 
 * 議事録の個別ページは全て非公開になるようにされているため、ここでは議事録のアーカイブページ
 * に非ログインユーザーからアクセスがあった場合に404エラーを返すようにしています。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名のため、同名の権限と混同しないように
 * 気を付けてください。
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 */
add_action( 'template_redirect', 'yadoken_minutes_404' );
function yadoken_minutes_404() {
  if( is_post_type_archive( 'yadoken_minutes' ) && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
  }
}

/**
 * ログインしている時、議事録のアーカイブリンクのリストで非公開記事を取得する。
 * 
 * 「アーカイブ」ウィジェットではwp_get_archives()でアーカイブリンクのリストを取得しているため、
 * その内部のgetarchives_whereフックで動作を変更しています。
 * 
 * wp_get_archives()はテーマ、プラグインによってはウィジェット以外でも使用されている
 * 可能性があるため、こちらに記述しています。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名のため、同名の権限と混同しないように
 * 気を付けてください。
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 * 
 * @param string $sql_where   SQLクエリ
 * @param array $parsed_args  デフォルト引数
 * @return string SQLクエリ
 */
add_filter( 'getarchives_where', 'yadoken_getarchives_where', 10, 2 );
function yadoken_getarchives_where( $sql_where, $parsed_args ) {
  if( $parsed_args['post_type'] === 'yadoken_minutes' && current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
    return str_replace( 'publish', 'private', $sql_where );
  } else {
    return $sql_where;
  }
}

/**
 * 全ての議事録を非公開にする。
 * 
 * フィルター名は{$new_status}_{$post->post_type}です。
 * 
 * @param int $post_id   投稿ID
 * @param WP_Post $post  投稿オブジェクト
 */
add_action( 'publish_yadoken_minutes', 'yadoken_force_private_minutes', 10, 2 );
function yadoken_force_private_minutes( $post_id, $post ) {
  $post->post_status = 'private';
  wp_update_post( $post );
}

/**
 * 投稿者が公開する記事を全て非公開にする。
 * 
 * フィルター名は{$new_status}_{$post->post_type}です。
 * 
 * @param int $post_id   投稿ID
 * @param WP_Post $post  投稿オブジェクト
 */
add_action( 'publish_post', 'yadoken_force_author_private_posts', 10, 2 );
function yadoken_force_author_private_posts( $ID, $post ) {
  if( current_user_can( 'author' ) ) {
    $post->post_status = 'private';
    wp_update_post( $post );
  }
}

/**
 * 投稿者の非公開記事表示権限追加
 * 
 * get_role()はWP_Roles()->get_role()のラッパーであり、WP_Roleオブジェクトを返します。
 * WP_Role()->add_cap()は内部でWP_Roles()->add_cap()を呼び出しています。
 * このメソッドではデータベースに値を保存しているため、このフックを削除しても
 * 変更は保存され続けます。
 */
add_action( 'admin_init', 'yadoken_allow_author_read_privates' );
function yadoken_allow_author_read_privates() {
  if( current_user_can( 'author' ) && ( ! current_user_can( 'read_private_posts' ) || ! current_user_can( 'read_private_pages' ) ) ) {
    $author = get_role( 'author' );
    $author->add_cap( 'read_private_posts' );
    $author->add_cap( 'read_private_pages' );
  }
}

/**
 * 投稿者の非公開記事表示権限削除
 * 
 * yadoken_allow_author_read_privates()を削除した際に、変更を元に戻すための関数です。
 * 元に戻した後はこちらも削除しましょう。
 */
//add_action( 'admin_init', 'yadoken_deny_author_read_privates' );
//function yadoken_deny_author_read_privates() {
//  if( current_user_can( 'author' ) && ( current_user_can( 'read_private_posts' ) || current_user_can( 'read_private_pages' ) ) ) {
//    $author = get_role( 'author' );
//    $author->remove_cap( 'read_private_posts' );
//    $author->remove_cap( 'read_private_pages' );
//  }
//}

/**
 * 投稿者の固定ページ下書き作成許可
 * 
 * get_role()はWP_Roles()->get_role()のラッパーであり、WP_Roleオブジェクトを返します。
 * WP_Role()->add_cap()は内部でWP_Roles()->add_cap()を呼び出しています。
 * このメソッドではデータベースに値を保存しているため、このフックを削除しても
 * 変更は保存され続けます。
 */
add_action( 'admin_init', 'yadoken_allow_author_edit_and_delete_pages' );
function yadoken_allow_author_edit_and_delete_pages() {
  if( current_user_can( 'author' ) && ( ! current_user_can( 'edit_pages' ) || ! current_user_can( 'delete_pages' ) ) ) {
    $author = get_role( 'author' );
    $author->add_cap( 'edit_pages' );
    $author->add_cap( 'delete_pages' );
  }
}

/**
 * 投稿者の固定ページ下書き作成権限削除
 * 
 * yadoken_allow_author_edit_and_delete_pages()を削除した際に、変更を元に戻すための関数です。
 * 元に戻した後はこちらも削除しましょう。
 */
//add_action( 'admin_init', 'yadoken_deny_author_edit_and_delete_pages' );
//function yadoken_deny_author_edit_and_delete_pages() {
//  if( current_user_can( 'author' ) && ( current_user_can( 'edit_pages' ) || current_user_can( 'delete_pages' ) ) ) {
//    $author = get_role( 'author' );
//    $author->remove_cap( 'edit_pages' );
//    $author->remove_cap( 'delete_pages' );
//  }
//}

/**
 * メニューページで選択肢に非公開記事を含める
 * 
 * 外観 > メニュー >メニュー項目を追加 の下のアコーディオンの中に表示される記事に
 * 非公開の記事が含まれるようにしました。
 * 
 * @param WP_Post_Type|WP_Taxonomy $post_type  投稿タイプオブジェクト
 * @return WP_Post_Type|WP_Taxonomy
 */
add_filter( 'nav_menu_meta_box_object', 'yadoken_nav_menu_meta_box_object' );
function yadoken_nav_menu_meta_box_object( $post_type ) {
  if( $post_type instanceof WP_Post_Type ) {
    $post_type->_default_query = array( 'post_status', 'publish,private' );
  }
  return $post_type;
}

/**
 * 内部ページのメニューをログインユーザーのみに表示
 * 
 * 投稿の状態がprivate(非公開)だった場合、その投稿を示すメニューアイテムを配列から消去しています。
 * また、親項目が削除された場合はその状態に関わらず当該のメニューアイテムも削除されます。
 * 
 * WP_Post_Type->cap->read_private_postsはプロパティ名のため、同名の権限と混同しないように
 * 気を付けてください。
 * 参照：https://developer.wordpress.org/reference/functions/get_post_type_capabilities/
 * 
 * @param array    $sorted_menu_items  ソート済みのメニューオブジェクトの配列
 * @param stdClass $args               wp_nav_menu()の引数オブジェクト(不使用)
 * @return array  ソート済みのメニューオブジェクトの配列
 */
add_filter( 'wp_nav_menu_objects', 'yadoken_remove_private_post_menu' );
function yadoken_remove_private_post_menu( $sorted_menu_items ) {
  $unset_ids = array();
  foreach( $sorted_menu_items as $key => $item ) {
    if( $item->type == 'post_type' ) {
      $post = get_post( $item->object_id );
      if( $post->post_status === 'private' && ! current_user_can( get_post_type_object( $post->post_type )->cap->read_private_posts ) ) {
        unset( $sorted_menu_items[$key] );
        $unset_ids[] = $item->ID;
        /** この時点で配列から取り除かれるため、foreach内の残りの処理をスキップしています。 */
        continue;
      }
    /** 議事録の非公開記事表示権限に合わせています。 */
    } elseif( $item->type === 'post_type_archive' ) {
      if( $item->object === 'yadoken_minutes' && ! current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
        unset( $sorted_menu_items[$key] );
        $unset_ids[] = $item->ID;
        /** この時点で配列から取り除かれるため、foreach内の残りの処理をスキップしています。 */
        continue;
      }
    }
    /**親項目が削除されていた場合、その子項目も削除 */
    if( in_array( $item->menu_item_parent, $unset_ids ) ) {
      unset( $sorted_menu_items[$key] );
      $unset_ids[] = $item->ID;
    }
  }
  return $sorted_menu_items;
}


/**
 * パーマリンク構造の変更とその対応
 * 
 * postの個別ページ、カテゴリーアーカイブ、タグアーカイブのurlの先頭に、
 * 設定 > 表示設定 > ホームページの表示 > 固定ページ > 投稿ページ で設定したページの
 * スラッグが付加されるように設定しています。
 */

/**
 * リライトルールの確認用コード
 * 
 * $wp_rewrite->flush_rules( false )は負荷が大きい操作のため、デバッグ時以外は”必ず”
 * コメントアウトするようにしてください。
 */
//add_action( 'init', 'yadoken_flush_rules' );
//function yadoken_flush_rules() {
//  global $wp_rewrite;
//  $wp_rewrite->flush_rules( false );  
//}
//add_filter( 'rewrite_rules_array', 'yadoken_rewrite_rules_array' );
//function yadoken_rewrite_rules_array( $rules ) {
//  var_dump( $rules );
//  return $rules;
//}

/**
 * パーマリンク構造が”基本”以外に設定されているときパーマリンク構造は空文字列となり、
 * リライトルールが生成されないためそれ以外のときに構造の変更をしています。
 */
if( get_option( 'permalink_structure' ) ) {

  /**
   * 活動報告のリライトルールの先頭に投稿ページのスラッグが付くようにする。
   * 
   * 通常、パーマリンク構造や投稿ページのスラッグを変更した場合は、設定 > パーマリンク設定 > 変更を保存
   * を押下しないと反映されません。この操作をした時に$wp_rewrite->flush_rules( false )が実行される
   * のですが、これが実行される時にしか実行されないフィルターということです。
   * 投稿ページ、もしくはそのスラッグが変更された場合は、yadoken_page_for_posts()もしくは
   * yadoken_save_post_page()でこれを実行して更新されるようにしています。
   * 
   * @param array $rewrite  リライトルール
   * @return array  新しいリライトルール
   */
  add_filter( 'post_rewrite_rules', 'yadoken_change_rewrite_rules' );
  add_filter( 'category_rewrite_rules', 'yadoken_change_rewrite_rules' );
  add_filter( 'post_tag_rewrite_rules', 'yadoken_change_rewrite_rules' );
  function yadoken_change_rewrite_rules( $rewrite ) {
    if( $page_for_posts = get_post( get_option( 'page_for_posts' ) ) ) {
      $new_rewrite = array();
      foreach( $rewrite as $key => $value ) {
        $new_rewrite[$page_for_posts->post_name . '/' . $key] = $value;
      }
      $rewrite = $new_rewrite;
    }
    return $rewrite;
  }

  /**
   * 投稿ページを変更した時にパーマリンクを更新する。
   * 
   * @param mixed $old_value  更新前の値(不使用)
   * @param mixed $value      更新後の値(不使用)
   * @param string $option    オプション名(不使用)
   */
  add_action( 'update_option_page_for_posts', 'yadoken_page_for_posts', 20 );
  function yadoken_page_for_posts() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules( false );
  }

  /**
   * 投稿ページのスラッグを更新した時にパーマリンクを更新する。
   * 
   * @param int $post_ID   投稿ID
   * @param WP_Post $post  投稿オブジェクト(不使用)
   * @param bool $update   更新された投稿かどうか(不使用)
   */
  add_action( 'save_post_page', 'yadoken_save_post_page', 20 );
  function yadoken_save_post_page( $post_ID ) {
    if( $post_ID === (int) get_option( 'page_for_posts' ) ) {
      global $wp_rewrite;
      $wp_rewrite->flush_rules( false );
    }
  }

  /**
   * リライトルールの変更に個別ページへのリンクを対応させる。
   * 
   * @param string $link  取得したリンク構造
   * 
   * @param WP_Post $post    投稿オブジェクト(不使用)
   * @param bool $leavename  投稿名を残すかどうか(不使用)
   * 
   * @param WP_Term $term  タームオブジェクト(不使用)
   * 
   * @return string  新しいリンク
   */
  add_filter( 'pre_post_link', 'yadoken_pre_link' );
  add_filter( 'pre_term_link', 'yadoken_pre_link' );
  function yadoken_pre_link( $link ) {
    $base = '';
    if( $page_for_posts = get_post( get_option( 'page_for_posts' ) ) ) {
      $base = '/' . $page_for_posts->post_name;
    }
    return $base . $link;
  }
}

?>