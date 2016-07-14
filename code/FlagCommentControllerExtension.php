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
		$id = $request->param('ID');
		if($id != (int) $id && $id > 0) {
			return $this->httpError(400, 'Invalid ID');
		}

		$comment = Comment::get()->byId($id);
		if(!$comment) {
			return $this->httpError(404);
		}

		if(!$comment->canFlag()) {
			return $this->httpError(403);
		}

		$response = $this->owner->getResponse();
		$response->getBody(json_encode(['flagged' => $comment->doFlag()]));
		return $response;
	}

}
