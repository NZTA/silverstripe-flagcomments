<?php

class FlagCommentControllerExtension extends Extension
{
	private static $allowed_actions = [
		'flagcomment',
		'unflagcomment',
		'removeflaggedcomment',
	];

	private static $url_handlers = [
		'flagcomment//$ID!' => 'flagComment',
		'unflagcomment//$ID' => 'unflagComment',
		'removeFlaggedComment//$ID' => 'removeFlaggedComment',
	];

	public function flagComment(SS_HTTPRequest $request)
	{
		// Check Security ID
		if(!SecurityToken::inst()->check($request->getVar('SecurityID'))) {
			return $this->owner->httpError(400);
		}

		$comment = $this->getComment($request);
		if(!$comment) {
			return $this->owner->httpError(404);
		}

		if(!$comment->canFlag()) {
			return Security::permissionFailure($this->owner);
		}

		$flagged = $comment->doFlag();
		if($request->isAjax()) {
			$response = $this->owner->getResponse();
			$response->addHeader('Content-Type', 'application/json');
			$response->setBody(json_encode(['flagged' => $flagged]));

			return $response;
		}

		return $this->owner->redirect($comment->Link());
	}

	public function unflagComment(SS_HTTPRequest $request)
	{
		$comment = $this->getComment($request);
		if(!$comment) {
			return $this->owner->httpError(404);
		}

		if(!$comment->canEdit()) {
			return $this->owner->httpError(403);
		}

		if(
			empty($comment->FlaggedSecurityToken)
			|| $comment->FlaggedSecurityToken != $request->getVar('token')
		) {
			return $this->owner->httpError(400);
		}

		$unflagged = $comment->doUnflag();
		if($request->isAjax()) {
			$response = $this->owner->getResponse();
			$response->addHeader('Content-Type', 'application/json');
			$response->setBody(json_encode(['unflagged' => $unflagged]));

			return $response;
		}

		return $this->owner->redirect($comment->Link());
	}

	public function removeFlaggedComment(SS_HTTPRequest $request)
	{
		$comment = $this->getComment($request);
		if(!$comment) {
			return $this->owner->httpError(404);
		}

		if(!$comment->canEdit()) {
			return $this->owner->httpError(403);
		}

		if(
			empty($comment->FlaggedSecurityToken)
			|| $comment->FlaggedSecurityToken != $request->getVar('token')
		) {
			return $this->owner->httpError(400);
		}

		$removed = $comment->doRemoveFlaggedComment();
		if($request->isAjax()) {
			$response = $this->owner->getResponse();
			$response->addHeader('Content-Type', 'application/json');
			$response->setBody(json_encode(['removed' => $removed]));

			return $response;
		}

		return $this->owner->redirect($comment->getParent()->Link());
	}

	protected function getComment(SS_HTTPRequest $request)
	{
		$id = $request->param('ID');
		if($id != (int) $id && $id > 0) {
			return false;
		}

		return Comment::get()->byId($id);
	}

}
