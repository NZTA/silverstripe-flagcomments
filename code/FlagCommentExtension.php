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
		return Controller::join_links(
			'CommentingController',
			'flagcomment',
			$this->owner->ID
		);
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

		if(!$this->owner->Flagged) {
			$this->owner->Flagged = true;
			try {
				$this->owner->write();
			} catch (Exception $e) {
				SS_Log::log($e->getMessage(), SS_Log::WARN);
				return false;
			}

			$this->notify();
		}

		return true;
	}
}
