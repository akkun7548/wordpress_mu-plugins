<?php
/*
 Plugin Name: カスタムウィジェット追加プラグイン
 Description: デフォルトで存在しているウィジェットの機能を拡張したものを追加しています。
 Version: 0.2
 Author: 野生動物研究会
 Author URI: https://www.stb.tsukuba.ac.jp/~yadoken/
*/

/**
 * ウィジェットの追加
 * 
 * - widgets_init
 *  - ウィジェットを定義・登録しています。
 *   - Yadoken_WP_Widget_Recent_Posts
 *   - Yadoken_WP_Widget_Archives
 */


/**
 * ウィジェットの定義・登録
 * 
 * mu-pluginsはウィジェットの定義より先に読み込まれるため、widgets_initフックを
 * 使用して実行タイミングを変更しています。
 */
add_action( 'widgets_init', function() {

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
  
        // コンストラクタ引数
        $widget_ops = array(
          'classname' => 'yadoken_widget_recent_entries',
          'description' => '最近の活動報告やお知らせ、議事録のリンクを表示します。このサイト独自の機能としてmu-pluginsで実装しています。',
          'customize_selective_refresh' => true,
        );
  
        // 祖父母クラスのコンストラクタ
        WP_Widget::__construct( 'yadoken-recent-posts', '最近の投稿(拡張版)', $widget_ops );
  
        $this->alt_option_name = 'yadoken_widget_recent_entries';
  
      }
  
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
        
        // WP_Queryコンストラクタの引数を追加
        add_filter( 'widget_posts_args', function( $query_args ) use( $post_type ) {
          return array_merge( $query_args, array( 'post_type' => $post_type ) );
        });
  
        // 親クラスのウィジェットを出力
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
  
        // 親クラスで定義された値を更新
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
  
        // 親クラスのフォームを出力
        parent::form( $instance );
  
        //カスタム投稿タイプにpostを合わせた投稿タイプスラッグの配列を取得しています。
        $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
        $post_type = isset( $instance['post_type'] ) ? $instance['post_type'] : 'post';
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php echo '投稿タイプ:'; ?></label>
        <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
        <?php foreach( (array) $post_types as $value ) {
          $name = ( $obj = get_post_type_object( $value ) ) ? $obj->labels->name : '' ;
          echo '<option value="' . esc_attr( $value ) . '"' . selected( $post_type, $value, false ) . '>' . esc_html( $name ) . '</option>' . "\n";
        } ?>
        </select>
        </p>
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
  
        // コンストラクタの引数
        $widget_ops = array(
            'classname'                   => 'yadoken_widget_archive',
            'description'                 => '投稿の期間別アーカイブ。このサイト独自の機能としてmu-pluginsで実装しています。',
            'customize_selective_refresh' => true,
        );
  
        // 祖父母クラスのコンストラクタ
        WP_Widget::__construct( 'yadoken_archives', 'アーカイブ(拡張版)', $widget_ops );
  
      }
  
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
  
        // アーカイブ表示期間
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
        
        // wp_get_archives()の引数の追加
        $add = array(
          'type' => $type,
          'limit' => $limit,
          'post_type' => $post_type
        );
  
        // ドロップダウンのフック
        add_filter( 'widget_archives_dropdown_args', function( $archives_dropdown_args ) use( $add ) {
          return array_merge( $archives_dropdown_args, $add );
        });
  
        // リストのフック
        add_filter( 'widget_archives_args', function( $archives_args ) use( $add ) {
          return array_merge( $archives_args, $add );
        });
  
        // 親クラスのウィジェットを出力
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
  
        // 親クラスで定義された値を更新
        $instance = parent::update( $new_instance, $old_instance );
  
        // インスタンスの引数のパース
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
  
        // 親クラスのフォームを出力
        parent::form( $instance );
  
        // インスタンス引数のパース
        $instance = wp_parse_args(
          (array) $instance,
          array(
            'type' => 'monthly',
            'limit' => 5,
            'post_type' => 'post'
          )
        );
  
        // アーカイブの期間の名前
        $types = array(
          'yearly' => '年別',
          'monthly' => '月別',
          'weekly' => '週別',
          'daily' => '日別',
        );
  
        //カスタム投稿タイプにpostを合わせた投稿タイプスラッグの配列を取得しています。
        $post_types = array_merge( array( 'post' ), get_post_types( array( '_builtin' => false, 'public' => true ) ) );
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php echo '期間:'; ?></label>
        <select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>">
        <?php foreach( (array) $types as $key => $value ) {
          echo '<option value="' . esc_attr( $key ) . '"' . selected( $instance['type'], $key, false ) . '>' . esc_html( $value ) . '</option>' . "\n";
        } ?>
        </select>
        </p>
  
        <p>
        <label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php echo '表示アーカイブ数の上限'; ?></label>
        <input class="tiny-text" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $instance['limit'] ); ?>" size="3" />
        </p>
  
        <p>
        <label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php echo '投稿タイプ:'; ?></label>
        <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
        <?php foreach( (array) $post_types as $value ) {
          $name = ( $obj = get_post_type_object( $value ) ) ? $obj->labels->name : '' ;
          echo '<option value="' . esc_attr( $value ) . '"' . selected( $instance['post_type'], $value, false ) . '>' . esc_html( $name ) . '</option>' . "\n";
        } ?>
        </select>
        </p>
        <?php
      }
  
    }
  
  
    // カスタムウィジェットのクラスを登録
    register_widget( 'Yadoken_WP_Widget_Recent_Posts' );
    register_widget( 'Yadoken_WP_Widget_Archives' );
  
});
  
?>