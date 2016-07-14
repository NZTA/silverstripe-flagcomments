<?php

class FlagCommentExtension extends DataExtension
{
	private static $db = [
		'Flagged' => 'Boolean',
	];

	/**
	 * Checks if the given user can flag a comment
	 *
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function canFlag(Member $member = null)
	{
		if($this->owner->Flagged) {
			return false;
		}

		$parent = $this->owner->getParent();
		$comments = $parent->config()->comments;

		return isset($comments['can_flag']) 
			&& $comments['can_flag'] 
			&& $parent->canPostComment($member); 
	}

	/**
	 * Returns a link to flag the current comment
	 *
	 * @return string
	 */
	public function FlagLink()
	{
		$link = Controller::join_links(
			'CommentingController',
			'flagcomment',
			$this->owner->ID
		);

		return HTTP::setGetVar('SecurityID', SecurityToken::inst()->getValue(), $link);
	}

	/**
	 * Flags the current coment
	 *
	 * @return bool
	 */
	public function doFlag()
	{
		if(!$this->owner->canFlag()) {
			return false;
		}

		$this->owner->Flagged = true;
		try {
			$this->owner->write();
		} catch (ValidationException $e) {
			SS_Log::log($e->getMessage(), SS_Log::WARN);
			return false;
		}

		$this->owner->extend('afterFlag');
		return true;
	}

}
