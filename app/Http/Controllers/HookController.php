<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BacklogIssue;
use Carbon\Carbon;

class HookController extends Controller
{
    // https://developer.nulab-inc.com/ja/docs/backlog/api/2/get-recent-updates/
	public function onHook(Request $request)
	{
        if(! $this->validateType($request)){
            return false;
        }

        $backlogIssue = new BacklogIssue($request);

        $this->onCreated($request, $backlogIssue);
        $this->onUpdated($request, $backlogIssue);
        exit;
    }

    private function validateType(Request $request)
    {
        if($request->input('type') === 1)  return true; // 課題の追加
        if($request->input('type') === 2)  return true; // 課題の更新
        if($request->input('type') === 3)  return true; // 課題にコメント
        if($request->input('type') === 4)  return true; // 課題の削除
        if($request->input('type') === 14) return true; // 課題をまとめて更新
        if($request->input('type') === 17) return true; // コメントにお知らせを追加

        return false;
    }

    // https://gist.github.com/alexstone/9319715
    private function sendToSlack(array $params)
    {
		$attachments = array([
			'fallback' => $params['summary'] . ' ' . $params['url'],
			'text'  => '#' . $params['keyId'] . ' <' . $params['url'] . '|' . $params['summary'] . '>',
			'color'    => '#ff6600',
			'fields'   => [
				[
					'title' => 'Status',
					'value' => $params['status'],
					'short' => true
				],
				[
					'title' => 'Assignee',
					'value' => $params['assignee'],
					'short' => true
				],
				[
					'title' => 'Priority',
					'value' => $params['priority'],
					'short' => true
				],
				[
					'title' => 'Due Date',
					'value' => $params['dueDate'],
					'short' => true
				],
				[
					'title' => 'Comment',
					'value' => $params['comment'],
					'short' => false,
				],
			]
		]);

		$data = "payload=" . json_encode([
			"channel"     => "#" . getenv('SLACK_CHANNEL'),
			"attachments" => $attachments,
		]);

		$ch = curl_init(getenv('SLACK_ENDPOINT'));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	private function onCreated(Request $request, BacklogIssue $issue)
	{
		if($request->input('type') !== 1) return false;

		$projectName    = $request->input('project.name');
		$projectKey     = $request->input('project.projectKey');
		$keyId          = $request->input('content.key_id');
		$createdUser    = $request->input('createdUser.name');
		$contentSummary = $request->input('content.summary');

		$this->sendToSlack([
            'summary'   => $contentSummary,
            'url'       => "https://{$projectName}.backlog.com/view/{$projectKey}-{$keyId}",
            'keyId'     => $keyId,
            'assignee'  => $issue['assignee']['name'],
            'status'    => $issue['status']['name'],
            'priority'  => $issue['priority']['name'],
            'dueDate'   => Carbon::parse($issue['dueDate'])->toDateString(),
            'comment'   => $issue['description'],
        ]);
		exit;
	}

	private function onUpdated(Request $request, BacklogIssue $issue)
	{
		$type = $request->input('type');
		if($type !== 2 && $type !== 3) return false;

		$projectName    = $request->input('project.name');
		$projectKey     = $request->input('project.projectKey');
		$keyId          = $request->input('content.key_id');
		$createdUser    = $request->input('createdUser.name');
		$contentSummary = $request->input('content.summary');
		$commentId      = $request->input('content.comment.id');
		$comment        = $request->input('content.comment.content');

		$this->sendToSlack([
            'summary'   => $contentSummary,
            'url'       => "https://{$projectName}.backlog.com/view/{$projectKey}-{$keyId}#comment-{$commentId}",
            'keyId'     => $keyId,
            'assignee'  => $issue['assignee']['name'],
            'status'    => $issue['status']['name'],
            'priority'  => $issue['priority']['name'],
            'dueDate'   => Carbon::parse($issue['dueDate'])->toDateString(),
            'comment'   => $comment,
        ]);
		exit;
	}
}
