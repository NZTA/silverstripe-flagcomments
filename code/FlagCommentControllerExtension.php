<?php

class FlagCommentControllerExtension extends Extension
{
	private static $allowed_actions = [
		'flagcomment',
	];

	private static $url_handlers = [
		'flagcomment//$ID!' => 'flagComment',
	];
		
	public function flagComment(SS_HTTPRequest $request)
	{
		// Check Security ID
		if(!$request->getVar('SecurityID')) {
			return $this->owner->httpError(400);
		}

		if(!SecurityToken::inst()->check($request->getVar('SecurityID'))) {
			return $this->owner->httpError(400);
		}

		$id = $request->param('ID');
		if($id != (int) $id && $id > 0) {
			return $this->owner->httpError(400, 'Invalid ID');
		}

		$comment = Comment::get()->byId($id);
		if(!$comment) {
			return $this->owner->httpError(404);
		}

		if(!$comment->canFlag()) {
			return $this->owner->httpError(403);
		}

		$response = $this->owner->getResponse();
		$response->getBody(json_encode(['flagged' => $comment->doFlag()]));

		return $response;
	}

}
