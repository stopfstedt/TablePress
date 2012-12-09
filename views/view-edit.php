<?php
/**
 * Edit Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Edit Table View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Edit_View extends TablePress_View {

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		$this->action_messages = array(
			'success_save' => __( 'The table was saved successfully.', 'tablepress' ),
			'success_add' => __( 'The table was added successfully.', 'tablepress' ),
			'success_import' => __( 'The table was imported successfully.', 'tablepress' ),
			'success_import_wp_table_reloaded' => __( 'The table was imported successfully from WP-Table Reloaded.', 'tablepress' ),
			'error_save' => __( 'Error: The table could not be saved.', 'tablepress' ),
			'error_delete' => __( 'Error: The table could not be deleted.', 'tablepress' ),
			'success_save_success_id_change' => __( 'The table was saved successfully, and the table ID was changed.', 'tablepress' ),
			'success_save_error_id_change' => __( 'The table was saved successfully, but the table ID could not be changed!', 'tablepress' )
		);

		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( 'error' == substr( $data['message'], 0, 5 ) || in_array( $data['message'], array( 'success_save_error_id_change' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		wp_enqueue_style( 'wp-jquery-ui-dialog' ); // do this here to get CSS into <head>
		add_action( 'admin_footer', array( $this, 'dequeue_media_upload_js' ), 2 ); // remove default media-upload.js, in favor of own code
		add_thickbox();

		// Use modified version of wpLink, instead of default version (changes "Title" to "Link Text")
		wp_deregister_script( 'wplink' );
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		// See wp-includes/script-loader.php for default parameters
		$wplink_url = plugins_url( "admin/tp_wplink{$suffix}.js", TABLEPRESS__FILE__ );
		wp_register_script( 'wplink', $wplink_url, array( 'jquery', 'wpdialogs' ), TablePress::version, true );
		wp_localize_script( 'wplink', 'wpLinkL10n', array(
			'title' => _x( 'Insert/edit link', 'Insert Link dialog', 'tablepress' ),
			'update' => _x( 'Update', 'Insert Link dialog', 'tablepress' ),
			'save' => _x( 'Add Link', 'Insert Link dialog', 'tablepress' ),
			'noTitle' => _x( '(no title)', 'Insert Link dialog', 'tablepress' ),
			'noMatchesFound' => _x( 'No matches found.', 'Insert Link dialog', 'tablepress' ),
			'link_text' => _x( 'Link Text', 'Insert Link dialog', 'tablepress' ) // Previous strings are default strings, this is the string that the modified tp_wplink.js inserts
		) );

		$this->admin_page->enqueue_style( 'edit' );
		$this->admin_page->enqueue_script( 'edit', array( 'jquery', 'jquery-ui-sortable', 'json2' ), array(
			'options' => array(
				'cells_advanced_editor' => apply_filters( 'tablepress_edit_cells_advanced_editor', true ),
				'cells_auto_grow' => apply_filters( 'tablepress_edit_cells_auto_grow', true ),
				'shortcode' => TablePress::$shortcode
			),
			'strings' => array_merge( array(
				'no_remove_all_rows' => __( 'You can not delete all table rows!', 'tablepress' ),
				'no_remove_all_columns' => __( 'You can not delete all table columns!', 'tablepress' ),
				'no_rows_selected' => __( 'You did not select any rows!', 'tablepress' ),
				'no_columns_selected' => __( 'You did not select any columns!', 'tablepress' ),
				'append_num_rows_invalid' => __( 'The value for the number of rows is invalid!', 'tablepress' ),
				'append_num_columns_invalid' => __( 'The value for the number of columns is invalid!', 'tablepress' ),
				'ays_remove_rows_singular' => _n( 'Do you really want to delete the selected row?', 'Do you really want to delete the selected rows?', 1, 'tablepress' ),
				'ays_remove_rows_plural' => _n( 'Do you really want to delete the selected row?', 'Do you really want to delete the selected rows?', 2, 'tablepress' ),
				'ays_remove_columns_singular' => _n( 'Do you really want to delete the selected column?', 'Do you really want to delete the selected columns?', 1, 'tablepress' ),
				'ays_remove_columns_plural' => _n( 'Do you really want to delete the selected column?', 'Do you really want to delete the selected columns?', 2, 'tablepress' ),
				'advanced_editor_open' => __( 'Please click into the cell that you want to edit using the &#8220;Advanced Editor&#8221;.', 'tablepress' ),
				'rowspan_add' => __( 'To combine cells within a column, click into the cell below the cell that has the content the combined cells shall have.', 'tablepress' ),
				'colspan_add' => __( 'To combine cells within a row, click into the cell to the right of the cell that has the content the combined cells shall have.', 'tablepress' ),
				'link_add' => __( 'Please click into the cell that you want to add a link to.', 'tablepress' ) . "\n" .
								__( 'You can then enter the Link URL and Text or choose an existing page or post.', 'tablepress' ),
				'image_add' => __( 'Please click into the cell that you want to add an image to.', 'tablepress' ) . "\n" .
								__( 'The Media Library will open, where you can select or upload the desired image or enter the image URL.', 'tablepress' ) . "\n" .
								sprintf( __( 'Click the &#8220;%s&#8221; button to insert the image.', 'tablepress' ), __( 'Insert into Post', 'default' ) ),
				'unsaved_changes_unload' => __( 'The changes to this table were not saved yet and will be lost if you navigate away from this page.', 'tablepress' ),
				'preparing_preview' => __( 'The Table Preview is being loaded...', 'tablepress' ),
				'preview_error' => __( 'The Table Preview could not be loaded.', 'tablepress' ),
				'save_changes_success' => __( 'Saving successful', 'tablepress' ),
				'save_changes_error' => __( 'Saving failed', 'tablepress' ),
				'saving_changes' => __( 'Changes are being saved...', 'tablepress' ),
				'table_id_not_empty' => __( 'The Table ID field can not be empty. Please enter a Table ID!', 'tablepress' ),
				'table_id_not_zero' => __( 'The Table ID &#8220;0&#8221; is not supported. Please enter a different Table ID!', 'tablepress' ),
				'ays_change_table_id' => __( 'Do you really want to change the Table ID? All Shortcodes for this table in your pages and posts will have to be adjusted!', 'tablepress' ),
				'extra_css_classes_invalid' => __( 'The entered value in the field &#8220;Extra CSS classes&#8221; is invalid.', 'tablepress' ),
				'num_pagination_entries_invalid' => __( 'The entered value in the field &#8220;Pagination Entries&#8221; is not a number.', 'tablepress' ),
				'sort_asc' => __( 'Sort ascending', 'tablepress' ),
				'sort_desc' => __( 'Sort descending', 'tablepress' ),
				'no_rowspan_first_row' => __( 'You can not add rowspan to the first row!', 'tablepress' ),
				'no_colspan_first_col' => __( 'You can not add colspan to the first column!', 'tablepress' ),
				'no_rowspan_table_head' => __( 'You can not connect cells into the table head row!', 'tablepress' ),
				'no_rowspan_table_foot' => __( 'You can not connect cells out of the table foot row!', 'tablepress' )
			), $this->action_messages ) // merge this to have messages available for AJAX after save dialog
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'buttons-1', array( $this, 'textbox_buttons' ), 'normal' );
		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( $this, 'postbox_table_information' ), 'normal' );
		$this->add_meta_box( 'table-data', __( 'Table Content', 'tablepress' ), array( $this, 'postbox_table_data' ), 'normal' );
		$this->add_meta_box( 'table-manipulation', __( 'Table Manipulation', 'tablepress' ), array( $this, 'postbox_table_manipulation' ), 'normal' );
		$this->add_meta_box( 'table-options', __( 'Table Options', 'tablepress' ), array( $this, 'postbox_table_options' ), 'normal' );
		$this->add_meta_box( 'datatables-features', __( 'Features of the DataTables JavaScript library', 'tablepress' ), array( $this, 'postbox_datatables_features' ), 'normal' );
		$this->add_text_box( 'hidden-containers', array( $this, 'textbox_hidden_containers' ), 'additional' );
		$this->add_text_box( 'buttons-2', array( $this, 'textbox_buttons' ), 'additional' );
		$this->add_text_box( 'other-actions', array( $this, 'textbox_other_actions' ), 'submit' );
	}

	/**
	 * Dequeue 'media-upload' JavaScript, which gets added by the Media Library,
	 * but is undesired here, as we have a custom function for this (send_to_editor()) and
	 * don't want the tb_position() function for resizing
	 *
	 * @since 1.0.0
	 */
	public function dequeue_media_upload_js() {
		wp_dequeue_script( 'media-upload' );
	}

	/**
	 * Print hidden field with a nonce for the screen's action, to be transmitted in HTTP requests
	 *
	 * @since 1.0.0
	 * @uses wp_nonce_field()
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 */
	protected function action_nonce_field( $data, $box ) {
		// use custom nonce field here, that includes the table ID
		wp_nonce_field( TablePress::nonce( $this->action, $data['table']['id'] ), 'nonce-edit-table' ); echo "\n";
		wp_nonce_field( TablePress::nonce( 'preview_table', $data['table']['id'] ), 'nonce-preview-table', false, true );
	}

	/**
	 * Print the content of the "Table Information" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_information( $data, $box ) {
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><label for="table-id"><?php _e( 'Table ID', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="hidden" name="table[id]" id="table-id" value="<?php echo esc_attr( $data['table']['id'] ); ?>" />
			<input type="text" name="table[new_id]" id="table-new-id" class="small-text" value="<?php echo esc_attr( $data['table']['id'] ); ?>" title="<?php esc_attr_e( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ); ?>" pattern="[A-Za-z0-9-_]+" required />
			<div style="float: right; margin-right: 1%;"><label for="table-information-shortcode"><?php _e( 'Shortcode', 'tablepress' ); ?>:</label>
			<input type="text" id="table-information-shortcode" class="table-shortcode" value="[<?php echo TablePress::$shortcode; ?> id=<?php echo esc_attr( $data['table']['id'] ); ?> /]" readonly="readonly" /></div>
		</td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><label for="table-name"><?php _e( 'Table Name', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><input type="text" name="table[name]" id="table-name" class="large-text" value="<?php echo esc_attr( $data['table']['name'] ); ?>" /></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="table-description"><?php _e( 'Description', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><textarea name="table[description]" id="table-description" class="large-text" rows="4"><?php echo esc_textarea( $data['table']['description'] ); ?></textarea></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Last Modified', 'tablepress' ); ?>:</th>
		<td class="column-2"><?php printf( __( '%1$s by %2$s', 'tablepress' ), '<span id="last-modified">' . TablePress::format_datetime( $data['table']['last_modified'] ) . '</span>', '<span id="last-editor">' . TablePress::get_user_display_name( $data['table']['options']['last_editor'] ) . '</span>' ); ?></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 * Print the content of the "Table Content" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_data( $data, $box ) {
		$table = $data['table']['data'];
		$options = $data['table']['options'];
		$visibility = $data['table']['visibility'];
		$rows = count( $table );
		$columns = count( $table[0] );

		$head_row_idx = $foot_row_idx = -1;
		// determine row index of the table head row, by excluding all hidden rows from the beginning
		if ( $options['table_head'] ) {
			for ( $row_idx = 0; $row_idx < $rows; $row_idx++ ) {
				if ( 1 === $visibility['rows'][$row_idx] ) {
					$head_row_idx = $row_idx;
					break;
				}
			}
		}
		// determine row index of the table foot row, by excluding all hidden rows from the end
		if ( $options['table_foot'] ) {
			for ( $row_idx = $rows - 1; $row_idx > -1; $row_idx-- ) {
				if ( 1 === $visibility['rows'][$row_idx] ) {
					$foot_row_idx = $row_idx;
					break;
				}
			}
		}
?>
<table id="edit-form">
	<thead>
		<tr id="edit-form-head">
			<th></th>
			<th></th>
<?php
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		$column_class = '';
		if ( 0 === $visibility['columns'][$col_idx] )
			$column_class = ' column-hidden';
		$column = TablePress::number_to_letter( $col_idx + 1 );
		echo "\t\t\t<th class=\"head{$column_class}\"><span class=\"sort-control sort-desc hide-if-no-js\" title=\"" . __( 'Sort descending', 'tablepress' ) . "\"><span class=\"sorting-indicator\"></span></span><span class=\"sort-control sort-asc hide-if-no-js\" title=\"" . __( 'Sort ascending', 'tablepress' ) . "\"><span class=\"sorting-indicator\"></span></span><span class=\"move-handle\">{$column}</span></th>\n";
	}
?>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr id="edit-form-foot">
			<th></th>
			<th></th>
<?php
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		$column_class = '';
		if ( 0 === $visibility['columns'][$col_idx] )
			$column_class = ' class="column-hidden"';
		echo "\t\t\t<th{$column_class}><input type=\"checkbox\" class=\"hide-if-no-js\" />";
		echo "<input type=\"hidden\" class=\"visibility\" name=\"table[visibility][columns][]\" value=\"{$visibility['columns'][$col_idx]}\" /></th>\n";
	}
?>
			<th></th>
		</tr>
	</tfoot>
	<tbody id="edit-form-body">
<?php
	foreach ( $table as $row_idx => $row_data ) {
		$row = $row_idx + 1;
		$classes = array();
		if ( $row_idx % 2 == 0 )
			$classes[] = 'odd';
		if ( $head_row_idx == $row_idx )
			$classes[] = 'head-row';
		elseif ( $foot_row_idx == $row_idx )
			$classes[] = 'foot-row';
		if ( 0 === $visibility['rows'][$row_idx] )
			$classes[] = 'row-hidden';
		$row_class = ( ! empty( $classes ) ) ? ' class="' . implode( ' ', $classes ) . '"' : '';
		echo "\t\t<tr{$row_class}>\n";
		echo "\t\t\t<td><span class=\"move-handle\">{$row}</span></td>";
		echo "<td><input type=\"checkbox\" class=\"hide-if-no-js\" /><input type=\"hidden\" class=\"visibility\" name=\"table[visibility][rows][]\" value=\"{$visibility['rows'][$row_idx]}\" /></td>";
		foreach ( $row_data as $col_idx => $cell ) {
			$column = TablePress::number_to_letter( $col_idx + 1 );
			$column_class = '';
			if ( 0 === $visibility['columns'][$col_idx] )
				$column_class = ' class="column-hidden"';
			$cell = esc_textarea( $cell ); // sanitize, so that HTML is possible in table cells
			echo "<td{$column_class}><textarea name=\"table[data][{$row_idx}][{$col_idx}]\" id=\"cell-{$column}{$row}\" rows=\"1\">{$cell}</textarea></td>";
		}
		echo "<td><span class=\"move-handle\">{$row}</span></td>\n";
		echo "\t\t</tr>\n";
	}
?>
	</tbody>
</table>
<input type="hidden" id="number-rows" name="table[number][rows]" value="<?php echo $rows; ?>" />
<input type="hidden" id="number-columns" name="table[number][columns]" value="<?php echo $columns; ?>" />
<?php
	}

	/**
	 * Print the content of the "Table Manipulation" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_manipulation( $data, $box ) {
		$media_library_url = esc_url( add_query_arg( array( 'post_id' => '0', 'type' => 'image', 'tab' => 'library' ), admin_url( 'media-upload.php' ) ) );
?>
<table class="tablepress-postbox-table fixed hide-if-no-js">
<tbody>
	<tr class="bottom-border">
		<td class="column-1">
			<input type="button" class="button" id="link-add" value="<?php _e( 'Insert Link', 'tablepress' ); ?>" />
			<a href="<?php echo $media_library_url; ?>" class="button" id="image-add"><?php _e( 'Insert Image', 'tablepress' ); ?></a>
			<input type="button" class="button" id="advanced-editor-open" value="<?php _e( 'Advanced Editor', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Combine cells', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="span-add-rowspan" value="<?php _e( 'in a column (rowspan)', 'tablepress' ); ?>" />
			<input type="button" class="button" id="span-add-colspan" value="<?php _e( 'in a row (colspan)', 'tablepress' ); ?>" />
			<input type="button" class="button show-help-box" value="<?php _e( '?', 'tablepress' ); ?>" title="<?php esc_attr_e( 'Help on combining cells', 'tablepress' ); ?>" />
			<div class="hidden-container hidden-help-box-container"><?php
				echo '<p>' . __( 'Table cells can span across more than one column or row.', 'tablepress' ) . '</p>';
				echo '<p>' . __( 'Combining consecutive cells within the same row is called &#8220;colspanning&#8221;.', 'tablepress' )
					. ' ' . __( 'Combining consecutive cells within the same column is called &#8220;rowspanning&#8221;.', 'tablepress' ) . '</p>';
				echo '<p>' . __( 'To combine adjacent cells in a row, add the keyword <code>#colspan#</code> to the cell to the right of the one with the content for the combined cell by using the corresponding button.', 'tablepress' )
					. ' ' . __( 'To combine adjacent cells in a column, add the keyword <code>#rowspan#</code> to the cell below the one with the content for the combined cell by using the corresponding button.', 'tablepress' ) . '</p>';
				echo '<p>' . __( 'Repeat this to add the keyword to all cells that shall be connected.', 'tablepress' ) . '</p>';
				echo '<p><strong>' . __( 'Be aware that the functions of the DataTables JavaScript library will not work on tables which have combined cells.', 'tablepress' ) . '</strong></p>';
			?></div>
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1">
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="rows-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button" id="rows-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="columns-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button" id="columns-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1">
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="rows-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button" id="rows-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="columns-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button" id="columns-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1">
			<?php printf( __( 'Add %s row(s)', 'tablepress' ), '<input type="number" id="rows-append-number" class="small-text numbers-only" title="' . __( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" maxlength="5" required />' ); ?>&nbsp;<input type="button" class="button" id="rows-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php printf( __( 'Add %s column(s)', 'tablepress' ), '<input type="number" id="columns-append-number" class="small-text numbers-only" title="' . __( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" maxlength="5" required />' ); ?>&nbsp;<input type="button" class="button" id="columns-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
	</tr>
</table>
<p class="hide-if-js"><?php _e( 'To use the Table Manipulation features, JavaScript needs to be enabled in your browser.', 'tablepress' ); ?></p>
<?php
	}

	/**
	 * Print the "Preview" and "Save Changes" button
	 *
	 * @since 1.0.0
	 */
	public function textbox_buttons( $data, $box ) {
		$preview_url = TablePress::url( array( 'action' => 'preview_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' );

		echo '<p class="submit">';
		if ( current_user_can( 'tablepress_preview_table', $data['table']['id'] ) )
			echo '<a href="' . $preview_url . '" class="button button-large show-preview-button" target="_blank">' . __( 'Preview', 'tablepress' ) . '</a>';
		?>
			<input type="button" class="button button-primary button-large save-changes-button hide-if-no-js" value="<?php _e( 'Save Changes', 'tablepress' ); ?>" />
			<input type="submit" class="button button-primary button-large hide-if-js" value="<?php _e( 'Save Changes', 'tablepress' ); ?>" />
		<?php
		echo '</p>';
	}

	/**
	 * Print the "Delete Table" and "Export Table" buttons
	 *
	 * @since 1.0.0
	 */
	public function textbox_other_actions( $data, $box ) {
		$user_can_delete_table = current_user_can( 'tablepress_delete_table', $data['table']['id'] );
		$user_can_export_table = current_user_can( 'tablepress_export_table', $data['table']['id'] );

		if ( ! $user_can_delete_table && ! $user_can_export_table )
			return;

		echo '<p class="submit">';
		echo __( 'Other Actions', 'tablepress' ) . ':&nbsp;';
		if ( $user_can_delete_table )
			echo '<a href="' . TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ) . '" class="button delete-link">' . __( 'Delete Table', 'tablepress' ) . '</a> ';
		if ( $user_can_export_table )
			echo '<a href="' . TablePress::url( array( 'action' => 'export', 'table_id' => $data['table']['id'] ) ) . '" class="button">' . __( 'Export Table', 'tablepress' ) . '</a>';
		echo '</p>';
	}

	/**
	 * Print the hidden containers for the Advanced Editor and the Preview
	 *
	 * @since 1.0.0
	 */
	public function textbox_hidden_containers( $data, $box ) {
?>
<div class="hidden-container">
	<div id="advanced-editor">
	<?php
		$wp_editor_options = array(
			'textarea_rows' => 10,
			'tinymce' => false,
			'quicktags' => array(
				'buttons' => 'strong,em,link,del,ins,img,code,spell,close'
			)
		);
		// temporarily disable "Add Media" button in Advanced Editor for WP 3.5
		$wp_editor_options['media_buttons'] = false;
		wp_editor( '', 'advanced-editor-content', $wp_editor_options );
	?>
	<div class="submitbox">
		<a href="#" class="submitdelete" id="advanced-editor-cancel"><?php _e( 'Cancel', 'tablepress' ); ?></a>
		<input type="button" class="button button-primary button-large" id="advanced-editor-confirm" value="<?php _e( 'OK', 'tablepress' ); ?>" />
	</div>
	</div>
</div>
<div id="preview-container" class="hidden-container">
	<div id="table-preview"></div>
</div>
<?php
	}

	/**
	 * Print the content of the "Table Options" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_options( $data, $box ) {
		$options = $data['table']['options'];
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr>
		<th class="column-1" scope="row"><?php _e( 'Table Head Row', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-table-head"><input type="checkbox" id="option-table-head" name="table[options][table_head]" value="true"<?php checked( $options['table_head'] ); ?> /> <?php _e( 'The first row of the table is the table header.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Table Foot Row', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-table-foot"><input type="checkbox" id="option-table-foot" name="table[options][table_foot]" value="true"<?php checked( $options['table_foot'] ); ?> /> <?php _e( 'The last row of the table is the table footer.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Alternating Row Colors', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-alternating-row-colors"><input type="checkbox" id="option-alternating-row-colors" name="table[options][alternating_row_colors]" value="true"<?php checked( $options['alternating_row_colors'] ); ?> /> <?php _e( 'The background colors of consecutive rows shall alternate.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Row Hover Highlighting', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-row-hover"><input type="checkbox" id="option-row-hover" name="table[options][row_hover]" value="true"<?php checked( $options['row_hover'] ); ?> /> <?php _e( 'Highlight a row while the mouse cursor hovers above it by changing its background color.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Print Table Name', 'tablepress' ); ?>:</th>
		<?php
			$position_select = '<select id="option-print-name-position" name="table[options][print_name_position]">';
			$position_select .= '<option' . selected( 'above', $options['print_name_position'], false ) . ' value="above">' . __( 'above', 'tablepress' ) . '</option>';
			$position_select .= '<option' . selected( 'below', $options['print_name_position'], false ) . ' value="below">' . __( 'below', 'tablepress' ) . '</option>';
			$position_select .= '</select>';
		?>
		<td class="column-2"><input type="checkbox" id="option-print-name" name="table[options][print_name]" value="true"<?php checked( $options['print_name'] ); ?> /> <?php printf( __( 'The table name shall be written %s the table.', 'tablepress' ), $position_select ); ?></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Print Table Description', 'tablepress' ); ?>:</th>
		<?php
			$position_select = '<select id="option-print-description-position" name="table[options][print_description_position]">';
			$position_select .= '<option' . selected( 'above', $options['print_description_position'], false ) . ' value="above">' . __( 'above', 'tablepress' ) . '</option>';
			$position_select .= '<option' . selected( 'below', $options['print_description_position'], false ) . ' value="below">' . __( 'below', 'tablepress' ) . '</option>';
			$position_select .= '</select>';
		?>
		<td class="column-2"><input type="checkbox" id="option-print-description" name="table[options][print_description]" value="true"<?php checked( $options['print_description'] ); ?> /> <?php printf( __( 'The table description shall be written %s the table.', 'tablepress' ), $position_select ); ?></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Extra CSS Classes', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-extra-css-classes"><input type="text" id="option-extra-css-classes" class="large-text" name="table[options][extra_css_classes]" value="<?php echo esc_attr( $options['extra_css_classes'] ); ?>" title="<?php esc_attr_e( 'This field can only contain letters, numbers, spaces, hyphens (-), and underscores (_).', 'tablepress' ); ?>" pattern="[A-Za-z0-9- _]*" /><p class="description"><?php echo __( 'Additional CSS classes for styling purposes can be entered here.', 'tablepress' ) . ' ' . sprintf( __( 'This is NOT the place to enter <a href="%s">Custom CSS</a> code!', 'tablepress' ), TablePress::url( array( 'action' => 'options' ) ) ); ?></p></label></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 * Print the content of the "Features of the DataTables JavaScript library" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_datatables_features( $data, $box ) {
		$options = $data['table']['options'];
?>
<p id="notice-datatables-head-row" class="hide-if-js"><?php printf( __( 'These features and options are only available, when the &#8220;%1$s&#8221; checkbox in the &#8220;%2$s&#8221; section is checked.', 'tablepress' ), __( 'Table Head Row', 'tablepress' ), __( 'Table Options', 'tablepress' ) ); ?></p>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Use DataTables', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-use-datatables"><input type="checkbox" id="option-use-datatables" name="table[options][use_datatables]" value="true"<?php checked( $options['use_datatables'] ); ?> /> <?php _e( 'Use the following features of the DataTables JavaScript library with this table:', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Sorting', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-sort"><input type="checkbox" id="option-datatables-sort" name="table[options][datatables_sort]" value="true"<?php checked( $options['datatables_sort'] ); ?> /> <?php _e( 'Enable sorting of the table by the visitor.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="no-border">
		<th class="column-1" scope="row"><?php _e( 'Search/Filtering', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-filter"><input type="checkbox" id="option-datatables-filter" name="table[options][datatables_filter]" value="true"<?php checked( $options['datatables_filter'] ); ?> /> <?php _e( 'Enable the visitor to filter or search the table. Only rows with the search word in them are shown.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="no-border">
		<th class="column-1" scope="row" style="vertical-align: top;"><?php _e( 'Pagination', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-paginate"><input type="checkbox" id="option-datatables-paginate" name="table[options][datatables_paginate]" value="true"<?php checked( $options['datatables_paginate'] ); ?> /> <?php _e( 'Enable pagination of the table (viewing only a certain number of rows at a time) by the visitor.', 'tablepress' ); ?></label><br />
		<label for="option-datatables-paginate_entries"><input type="checkbox" style="visibility: hidden;" <?php // Dummy checkbox for space alignment ?>/> <?php printf( __( 'Show %s rows per page.', 'tablepress' ), '<input type="number" id="option-datatables-paginate_entries" name="table[options][datatables_paginate_entries]" value="' . $options['datatables_paginate_entries'] . '" min="1" max="99999" maxlength="5" required />' ); ?></label></td>
	</tr>
	<tr class="no-border">
		<th class="column-1" scope="row"><?php _e( 'Pagination Length Change', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-lengthchange"><input type="checkbox" id="option-datatables-lengthchange" name="table[options][datatables_lengthchange]" value="true"<?php checked( $options['datatables_lengthchange'] ); ?> /> <?php _e( 'Allow the visitor to change the number of rows shown when using pagination.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="no-border">
		<th class="column-1" scope="row"><?php _e( 'Info', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-info"><input type="checkbox" id="option-datatables-info" name="table[options][datatables_info]" value="true"<?php checked( $options['datatables_info'] ); ?> /> <?php _e( 'Enable the table information display, with information about the currently visible data, like the number of rows.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="<?php echo current_user_can( 'unfiltered_html' ) ? 'bottom-border' : 'no-border'; ?>">
		<th class="column-1" scope="row"><?php _e( 'Horizontal Scrolling', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-scrollx"><input type="checkbox" id="option-datatables-scrollx" name="table[options][datatables_scrollx]" value="true"<?php checked( $options['datatables_scrollx'] ); ?> /> <?php _e( 'Enable horizontal scrolling, to make viewing tables with many columns easier.', 'tablepress' ); ?></label></td>
	</tr>
	<?php
		// "Custom Commands" must only be available to trusted users
	?>
	<tr class="<?php echo current_user_can( 'unfiltered_html' ) ? 'top-border' : 'hidden'; ?>">
		<th class="column-1" scope="row"><?php _e( 'Custom Commands', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables-custom-commands"><input type="text" id="option-datatables-custom-commands" class="large-text" name="table[options][datatables_custom_commands]" value="<?php echo esc_attr( $options['datatables_custom_commands'] ); ?>" /><p class="description"><?php echo __( 'Additional parameters from the <a href="http://www.datatables.net/">DataTables documentation</a> to be added to the JS call.', 'tablepress' ) . ' ' . __( 'For advanced use only.', 'tablepress' ); ?></p></label></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 * Print the screen head text
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
	<p>
		<?php printf( __( 'On this screen, you can edit the content and structure of the table with the ID %s.', 'tablepress' ), esc_html( $data['table']['id'] ) ); ?>
		<?php _e( 'For example, you can insert things like text, images, or links into the table, or change the used table features. You can also insert, delete, move, hide, and swap columns and rows.', 'tablepress' ); ?>
	</p>
	<p>
		<?php printf( __( 'To insert the table into a page, post, or text widget, copy the Shortcode %s and paste it at the desired place in the editor.', 'tablepress' ), '<input type="text" class="table-shortcode table-shortcode-inline" value="[' . TablePress::$shortcode . ' id=' . esc_attr( $data['table']['id'] ) . ' /]" readonly="readonly" />' ); ?>
	</p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Edit Table screen';
	}

} // class TablePress_Edit_View