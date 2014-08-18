<div class="col-sm-4 clearfix">
	<div id="algolia">
		<form method="get" action="{$algolia_search_url}" class="row">
			<input type="text" id="algolia-search" name="q" class="typeahead" autocomplete="off" class="autocomplete" {if isset($algolia_query)} value="{$algolia_query}"{/if} />
		</form>
	</div>
</div>
