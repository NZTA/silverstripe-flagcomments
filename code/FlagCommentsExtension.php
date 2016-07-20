<?php

class FlagCommentsExtension extends DataExtension
{
	/**
	 * Filters flagged and removed comments
	 *
	 * @param SS_List $list
	 */
	public function updateAllVisibleComments(SS_List &$list)
	{
		$list = $list->filter('FlaggedAndRemoved', false);	
	}
}
