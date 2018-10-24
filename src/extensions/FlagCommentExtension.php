<?php

namespace NZTA\FlagComments\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Security\Member;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Control\HTTP;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Controller;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\SS_List;

class FlagCommentExtension extends DataExtension
{
	/**
	 * @var array
	 */
	private static $db = [
		'Flagged' => 'Boolean',
		'FlaggedAndRemoved' => 'Boolean',
		'FlaggedSecurityToken' => 'Varchar(255)',
	];

	/**
	 * @param FieldList $fields
	 * @return void
	 */
	public function updateCMSFields(FieldList $fields)
	{
		$optionField = null;
		foreach($fields as $field) {
			if(get_class($field) == 'FieldGroup'  && $field->Name() == 'Options') {
				$field->push(CheckboxField::create('Flagged', 'Flagged?'));
				break;
			}
		}
	}

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
	 * Returns a link to remove a flagged comment
	 *
	 * @return string
	 */
	public function RemoveFlaggedCommentLink()
	{
		$link = Controller::join_links(
			'CommentingController',
			'removeflaggedcomment',
			$this->owner->ID
		);

		return HTTP::setGetVar('token', $this->owner->FlaggedSecurityToken, $link);
	}

	/**
	 * Returns a link to unflag a comment
	 *
	 * @return string
	 */
	public function UnflagLink()
	{
		$link = Controller::join_links(
			'CommentingController',
			'unflagcomment',
			$this->owner->ID
		);

		return HTTP::setGetVar('token', $this->owner->FlaggedSecurityToken, $link);
	}

	/**
	 * Flags the current comment
	 *
	 * @return bool
	 */
	public function doFlag()
	{
		if(!$this->owner->canFlag()) {
			return false;
		}

		$this->owner->Flagged = true;
		$this->owner->FlaggedAndRemoved = false;

		$random = new RandomGenerator();
		$this->owner->FlaggedSecurityToken = $random->randomToken();

		try {
			$this->owner->write();
		} catch (ValidationException $e) {
			Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
			return false;
		}

		$this->owner->extend('afterFlag');
		return true;
	}

	/**
	 * Remove the flag on a comment
	 *
	 * @return bool
	 */
	public function doUnflag()
	{
		if(!$this->owner->canEdit()) {
			return false;
		}

		$this->owner->Flagged = false;
		$this->owner->FlaggedAndRemoved = false;
		$this->owner->FlaggedSecurityToken = null;
		try {
			$this->owner->write();
		} catch (ValidationException $e) {
			Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
			return false;
		}

		$this->owner->extend('afterUnflag');
		return true;
	}

	/**
	 * Remove a comment which has been flagged
	 *
	 * @return bool
	 */
	public function doRemoveFlaggedComment()
	{
		if(!$this->owner->canEdit()) {
			return false;
		}

		if(!$this->owner->Flagged) {
			return false;
		}

		$this->owner->FlaggedAndRemoved = true;
		$this->owner->FlaggedSecurityToken = null;
		try {
			$this->owner->write();
		} catch (ValidationException $e) {
			Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Filters flagged and removed replies
	 *
	 * @param SS_List $list
	 */
	public function updateReplies(SS_List &$list)
	{
		$list = $list->filter('FlaggedAndRemoved', false);
	}
}
