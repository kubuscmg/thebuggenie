<input class="button button-silver" id="search_more_actions_button" type="button" style="float: right;" value="<?php echo __('More actions'); ?>" onclick="$(this).toggleClassName('button-pressed');$('search_more_actions').toggle();">
<ul id="search_more_actions" style="display: none; font-size: 0.9em; right: 0px; margin-top: -1px;" class="simple_list rounded_box white shadowed more_actions_dropdown" onclick="$('search_more_actions_button').toggleClassName('button-pressed');$('search_more_actions').toggle();">
	<li class="header"><?php echo __('Additional actions available'); ?></li>
	<li id="search_builder_toggler"><a href="javascript:void(0);" onclick="$('search_builder').toggle();"><?php echo __('Refine search'); ?></a></li>
	<?php if ($show_results): ?>
		<?php if (!$tbg_user->isGuest() && !$issavedsearch): ?>
			<li id="save_search_builder_toggler"><a href="javascript:void(0);" onclick="$(this).toggle();$('search_builder').toggle();$('find_issues_form').method = 'post';$('saved_search_details').show();$('saved_search_name').enable();$('saved_search_name').focus();$('saved_search_description').enable();<?php if ($tbg_user->canCreatePublicSearches()): ?>$('saved_search_public').enable();<?php endif; ?>$('save_search').enable();$('search_button_bottom').disable();$('search_button_bottom').hide();$('search_button_top').disable();$('search_button_save').hide();$('search_button_top').hide();return false;"><?php echo __('Save this search'); ?></a></li>
		<?php endif; ?>
	<?php endif; ?>
	<li id="search_column_settings_toggler" style="display: none;"><a href="javascript:void(0);" onclick="$('search_column_settings_container').toggle();" title="<?php echo __('Configure visible columns'); ?>"><?php echo __('Configure visible columns'); ?></a></li>
	<li id="search_column_settings_notoggler" class="disabled"><a href="javascript:void(0);"><?php echo __("Configure visible columns"); ?></a><div class="tooltip rightie" style="font-weight: normal;"><?php echo __('This issue list template does not support configuring visible columns'); ?></div></li>
	<li class="header" style="margin-top: 10px;"><?php echo __('Download search results'); ?></li>
	<?php if (isset($csv_url)): ?>
		<li><a href="<?php echo $csv_url; ?>"><?php echo image_tag('icon_csv.png') . __('Download as CSV'); ?></a></li>
	<?php endif; ?>
	<?php if (isset($rss_url)): ?>
		<li><a href="<?php echo $rss_url; ?>"><?php echo image_tag('icon_rss.png') . __('Download as RSS'); ?></a></li>
	<?php endif; ?>
</ul>
