<?php
/**
 * ウィジェット関係
 * 
 * テーマを切り替えた時も残す必要があるウィジェットの定義などをこちらにまとめました。
 * 
 * 以下のクラスを拡張したクラスを定義しています。
 * ・WP_Widget_Recent_Posts
 * ・WP_Widget_Archives
 * 
 * 新たに定義したクラスの登録と、その元になった機能が重複するクラスの登録を解除しています。
 * 
 * 全てのウィジェットに以下の機能を追加しています。
 * ・選択したページのみで表示する。
 * ・ログインユーザーのみに表示する。
 */

/**
 * ウィジェットの定義・登録
 * 
 * mu-pluginsはウィジェットの定義より先に読み込まれるため、widgets_initフックを
 * 使用して実行タイミングを変更しています。
 */
add_action( 'widgets_init', 'yadoken_register_custom_widgets' );
function yadoken_register_custom_widgets() {

  /**
   * 最近の投稿を表示するウィジェットに以下の機能を追加
   * ・投稿タイプ選択機能
   * 
   * 参照：https://developer.wordpress.org/reference/classes/wp_widget_recent_posts/
   */
  class Yadoken_WP_Widget_Recent_Posts extends WP_Widget_Recent_Posts {

    /**
     * コンストラクタ
     * 
     * 設定画面に表示される説明などを設定できます。
     */
    public function __construct() {
      $widget_ops = array(
        'classname' => 'yadoken_widget_recent_entries',
        'description' => '直近の投稿。このサイト独自の機能としてmu-pluginsで実装しています。',
        'customize_selective_refresh' => true,
      );
      WP_Widget::__construct( 'yadoken-recent-posts', '最近の投稿(拡張版)', $widget_ops );
      $this->alt_option_name = 'yadoken_widget_recent_entries';
    }

    /**
     * parent::widget()のコールバック内で使用する変数
     * 
     * @var array
     */
    private $yadoken_add;

    /**
     * 実際のサイト内の設定した箇所にHTMLを出力するメゾット
     * 
     * WP_Queryオブジェクトのインスタンスを作成する際のオプションにpost_typeを追加し、
     * カスタム投稿タイプも取得できるようにしています。
     * 
     * @param array $args      表示するものの連想配列
     * @param array $instance  現在のインスタンスの連想配列
     */
    public function widget( $args, $instance ) {
      /**
       * post_typeの値をサニタイズするため、フォームでの選択肢と同様に投稿タイプ名の配列を取得して
       * その値に含まれるか調べています。
       */
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      $post_type = isset( $instance['post_type'] ) && in_array( $instance['post_type'], $post_types, true ) ? $instance['post_type'] : 'post';
      $this->yadoken_add = array( 'post_type' => $post_type );
      add_filter( 'widget_posts_args', function( $query_args ) {
        return array_merge( $query_args, $this->yadoken_add );
      } );
      parent::widget( $args, $instance );
    }

    /**
     * ウィジェットの更新をするメゾット
     * 
     * 設定画面で保存を押した時に、設定を保存します。
     * 
     * @param array $new_instance  設定画面からの入力値の連想配列
     * @param array $old_instance  同じインスタンスの更新前の設定
     * @return array 保存する更新後の設定の連想配列
     */
    public function update( $new_instance, $old_instance ) {
      $instance = parent::update( $new_instance, $old_instance );
      /**
       * post_typeの値をサニタイズするため、フォームでの選択肢と同様に投稿タイプ名の配列を取得して
       * その値に含まれるか調べています。
       */
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      $instance['post_type'] = isset( $new_instance['post_type'] ) && in_array( $new_instance['post_type'], $post_types, true ) ? $new_instance['post_type'] : 'post';
      return $instance;
    }

    /**
     * ウィジェットの設定フォームを出力するメゾット
     * 
     * 設定画面にフォームを出力します。
     * 存在しているカスタム投稿タイプ全てに活動報告を合わせたものをリストアップして、
     * プルダウンメニューで選択できるようにしています。
     * 
     * @param string $instance  現在の設定の連想配列
     */
    public function form( $instance ) {
      parent::form( $instance );
      //カスタム投稿タイプにpostを合わせた投稿タイプスラッグの配列を取得しています。
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      $post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
      ?>
      <p><label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php echo '投稿タイプ:'; ?></label>
      <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
      <?php foreach( (array) $post_types as $value ) {
        $name = ( $obj = get_post_type_object( $value ) ) ? $obj->labels->name : '' ;
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $post_type, $value, false ) . '>' . esc_html( $name ) . '</option>' . "\n";
      } ?>
      </select></p>
      <?php
    }
  }


  /**
   * アーカイブのウィジェットに以下の機能を追加
   * ・投稿タイプ選択機能
   * ・期間選択機能
   * ・取得数制限機能
   * 
   * 参照：https://developer.wordpress.org/reference/classes/wp_widget_archives/
   */
  class Yadoken_WP_Widget_Archives extends WP_Widget_Archives {

    /**
     * コンストラクタ
     * 
     * 設定画面に表示される説明などを設定できます。
     */
    public function __construct() {
        $widget_ops = array(
            'classname'                   => 'yadoken_widget_archive',
            'description'                 => '投稿の期間別アーカイブ。このサイト独自の機能としてmu-pluginsで実装しています。',
            'customize_selective_refresh' => true,
        );
        WP_Widget::__construct( 'yadoken_archives', 'アーカイブ(拡張版)', $widget_ops );
    }

    /**
     * parent::widget()のコールバック内で使用するための変数
     * 
     * @var array
     */
    private $yadoken_add;

    /**
     * 実際のサイト内に設定した箇所にHTMLを出力するメゾット
     * 
     * WP_Queryオブジェクトのインスタンスを作成する際のオプションにpost_typeを追加し、
     * カスタム投稿タイプも取得できるようにしています。
     * また、日別から年別まで投稿を取得する期間も指定できるようにしています。
     * 取得アーカイブ数の上限設定も追加しています。
     * 
     * @param array $args      表示するものの連想配列
     * @param array $instance  現在のインスタンスの連想配列
     */
    public function widget( $args, $instance ) {
      $types = array( 'yearly', 'monthly', 'daily', 'weekly' );
      $type = isset( $instance['type'] ) && in_array( $instance['type'], $types, true ) ? $instance['type'] : 'monthly';
      //WP_Widget_Recent_Postsの記事取得数($number)を参考にしています。
      $limit = ( ! empty( $instance['limit'] ) ) ? absint( $instance['limit'] ) : 5;
      if ( ! $limit ) {
        $limit = 5;
      }
      /**
       * post_typeの値をサニタイズするため、フォームでの選択肢と同様に投稿タイプ名の配列を取得して
       * その値に含まれるか調べています。
       */
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      $post_type = isset( $instance['post_type'] ) && in_array( $instance['post_type'], $post_types, true ) ? $instance['post_type'] : 'post' ;
      $this->yadoken_add = array(
        'type' => $type,
        'limit' => $limit,
        'post_type' => $post_type
      );
      add_filter( 'widget_archives_dropdown_args', function( $archives_dropdown_args ) {
        return array_merge( $archives_dropdown_args, $this->yadoken_add );
      } );
      add_filter( 'widget_archives_args', function( $archives_args ) {
        return array_merge( $archives_args, $this->yadoken_add );
      } );
      parent::widget( $args, $instance );
    }

    /**
     * ウィジェットの更新をするメゾット
     * 
     * 設定画面で保存を押した時に、設定を保存します。
     * 
     * @param array $new_instance  設定画面からの入力値の連想配列
     * @param array $old_instance  同じインスタンスの更新前の設定
     * @return array 保存する更新後の設定の連想配列
     */
    public function update( $new_instance, $old_instance ) {
      $instance = parent::update( $new_instance, $old_instance );
      $new_instance = wp_parse_args(
        (array) $new_instance,
        array(
          'type' => 'monthly',
          'limit' => 5,
          'post_type' => 'post'
        )
      );
      $types = array( 'yearly', 'monthly', 'daily', 'weekly' );
      $instance['type']  = in_array( $new_instance['type'], $types, true ) ? $new_instance['type'] : 'monthly';
      $instance['limit'] = (int) $new_instance['limit'];
      /**
       * post_typeの値をサニタイズするため、フォームでの選択肢と同様に投稿タイプ名の配列を取得して
       * その値に含まれるか調べています。
       */
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      $instance['post_type'] = in_array( $new_instance['post_type'], $post_types, true ) ? $new_instance['post_type'] : 'post';
      return $instance;
    }

    /**
     * ウィジェットの設定フォームを出力するメゾット
     * 
     * 設定画面にフォームを出力します。
     * 存在しているカスタム投稿タイプ全てに活動報告を合わせたものをリストアップして、
     * プルダウンメニューで選択できるようにしています。
     * また、日別から年別まで投稿を取得する期間も指定できるようにしています。
     * 
     * @param string $instance  現在の設定の連想配列
     */
    public function form( $instance ) {
      parent::form( $instance );
      $instance = wp_parse_args(
        (array) $instance,
        array(
          'type' => 'monthly',
          'limit' => 5,
          'post_type' => 'post'
        )
      );
      $types = array(
        'yearly' => '年別',
        'monthly' => '月別',
        'weekly' => '週別',
        'daily' => '日別',
      );
      //カスタム投稿タイプにpostを合わせた投稿タイプスラッグの配列を取得しています。
      $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
      ?>
      <p><label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php echo '期間:'; ?></label>
      <select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>">
      <?php foreach( (array) $types as $key => $value ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $instance['type'], $key, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
      } ?>
      </select></p>

      <p><label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php echo '表示アーカイブ数の上限'; ?></label>
      <input class="tiny-text" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $instance['limit'] ); ?>" size="3" /></p>

      <p><label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php echo '投稿タイプ:'; ?></label>
      <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
      <?php foreach( (array) $post_types as $value ) {
        $name = ( $obj = get_post_type_object( $value ) ) ? $obj->labels->name : '' ;
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $instance['post_type'], $value, false ) . '>' . esc_html( $name ) . '</option>' . "\n";
      } ?>
      </select></p>
      <?php
    }
  }

  //カスタムウィジェットのクラスを登録
  register_widget( 'Yadoken_WP_Widget_Recent_Posts' );
  register_widget( 'Yadoken_WP_Widget_Archives' );

  //元からある機能が重複するウィジェットを削除
  unregister_widget( 'WP_Widget_Recent_Posts' );
  unregister_widget( 'WP_Widget_Archives' );
}

/**
 * 「最近の投稿」ウィジェットで権限保有時に非公開の議事録を取得
 * 
 * post_typeが設定された後にこのコールバックを適用するため、優先度を下げています。
 * 
 * @param array $args      投稿を取得する引数
 * @param array $instance  ウィジェットのインスタンス変数(不使用)
 * @return array  投稿を取得する引数
 */
add_filter( 'widget_posts_args', 'yadoken_widget_posts_args', 100 );
function yadoken_widget_posts_args( $args ) {
  if( isset( $args['post_type'] ) && $args['post_type'] === 'yadoken_minutes' && current_user_can( get_post_type_object( 'yadoken_minutes' )->cap->read_private_posts ) ) {
    $args['post_status'] = 'private';
  }
  return $args;
}



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
add_action( 'in_widget_form', 'yadoken_in_widget_form', 10, 3 );
function yadoken_in_widget_form( $widget, $return, $instance ) {
  $internal = isset( $instance['yadoken_internal'] ) ? (bool) $instance['yadoken_internal'] : false;
  ?>
  <p><input class="checkbox" type="checkbox"<?php checked( $internal ); ?> id="<?php echo $widget->get_field_id( 'yadoken_internal' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_internal' ); ?>" />
  <label for="<?php echo $widget->get_field_id( 'yadoken_internal' ); ?>"><?php echo 'ログインユーザー向け'; ?></label></p>

  <?php
  //セットされていない場合の初期値としてtrue、つまり全ページで表示するように設定しています。
  $all_display = isset( $instance['yadoken_all_display'] ) ? (bool) $instance['yadoken_all_display'] : true;
  ?>
  <p><input class="checkbox" type="checkbox"<?php checked( $all_display ); ?> id="<?php echo $widget->get_field_id( 'yadoken_all_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_all_display' ); ?>" />
  <label for="<?php echo $widget->get_field_id( 'yadoken_all_display' ); ?>"><?php echo '全ページで表示'; ?></label></p>
  <?php
  if( ! $all_display ) {

    $args = array( 'post_status' => 'publish,pending,private' );
    if( $pages = get_pages( $args ) ) {
      //管理画面にはstyle.cssが読み込まれないため、属性値としてスタイリングしました。 ?>
      <p style="font-size: 1.1rem; margin: 5px 0;"><?php echo '固定ページ'; ?></p>
      <p style="border: 2px solid #eeeeee; border-radius: 3px; padding: 5px; margin: -2px -2px 9px -2px;"><?php
      foreach( (array) $pages as $page ) {
        $key = $page->ID;
        if( $key === (int) get_option( 'page_for_posts' ) ) {
          continue;
        }
        $page_display[$key] = ( isset( $instance['yadoken_page_display'] ) && in_array( $key, array_map( 'intval', $instance['yadoken_page_display'] ), true ) ) ? true : false;
        ?>
          <input class="checkbox" type="checkbox"<?php checked( $page_display[$key] ); ?> id="<?php echo $widget->get_field_id( 'yadoken_page_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_page_display' ) . '[]'; ?>" value="<?php echo esc_attr( $key ); ?>" />
          <label for="<?php echo $widget->get_field_id( 'yadoken_page_display' ); ?>"><?php echo esc_html( $page->post_title ); ?></label>
        <?php
      } ?>
      </p><?php
    }

    $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
    ?>
    <p style="font-size: 1.1rem; margin: 5px 0;"><?php echo '投稿タイプ'; ?></p>
    <p style="border: 2px solid #eeeeee; border-radius: 3px; padding: 5px; margin: -2px -2px 9px -2px;"><?php
    foreach( $post_types as $key ) {
      $post_type_display[$key] = ( isset( $instance['yadoken_post_type_display'] ) && in_array( $key, $instance['yadoken_post_type_display'], true ) ) ? true : false;
      ?>
        <input class="checkbox" type="checkbox"<?php checked( $post_type_display[$key] ); ?> id="<?php echo $widget->get_field_id( 'yadoken_post_type_display' ); ?>" name="<?php echo $widget->get_field_name( 'yadoken_post_type_display' ) . '[]'; ?>" value="<?php echo esc_attr( $key ); ?>" />
        <label for="<?php echo $widget->get_field_id( 'yadoken_post_type_display' ); ?>"><?php echo ( $obj = get_post_type_object( $key ) ) ? $obj->labels->name : '' ; ?></label>
      <?php
    } ?>
    </p><?php

  }
}

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
add_filter( 'widget_update_callback', 'yadoken_widget_update_callback', 10, 2 );
function yadoken_widget_update_callback( $instance, $new_instance ) {
  $instance['yadoken_internal'] = isset( $new_instance['yadoken_internal'] ) ? (bool) $new_instance['yadoken_internal'] : false;
  $instance['yadoken_all_display'] = isset( $new_instance['yadoken_all_display'] ) ? (bool) $new_instance['yadoken_all_display'] : false;
  $instance['yadoken_page_display'] = array();
  if( isset( $new_instance['yadoken_page_display'] ) ) {
    foreach( (array) $new_instance['yadoken_page_display'] as $value ) {
      $instance['yadoken_page_display'][] = $value;
    }
  }
  $instance['yadoken_post_type_display'] = array();
  if( isset( $new_instance['yadoken_post_type_display'] ) ) {
    foreach( (array) $new_instance['yadoken_post_type_display'] as $value ) {
      $instance['yadoken_post_type_display'][] = $value;
    }
  }
  return $instance;
}

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
add_filter( 'widget_display_callback', 'yadoken_widget_display_callback', 10, 3 );
function yadoken_widget_display_callback( $instance, $widget, $args ) {
  if( isset( $instance['yadoken_internal'] ) && $instance['yadoken_internal'] && ! is_user_logged_in() ) {
    $instance = false;
  } elseif( isset( $instance['yadoken_all_display'] ) && ! $instance['yadoken_all_display'] ) {
    if( is_page() ) {
      if( isset( $instance['yadoken_page_display'] ) ) {
        if( ! in_array( get_the_ID(), array_map( 'intval', $instance['yadoken_page_display'] ), true ) ) {
          $instance = false;
        }
      } else {
        $instance = false;
      }
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
}

?>