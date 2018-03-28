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
        if($this->isIssue($request)){
            $backlogIssue = new BacklogIssue($request);
            $this->onIssueCreated($request, $backlogIssue);
            $this->onIssueUpdated($request, $backlogIssue);
            exit;
        }

        if($this->isWiki($request)){
            $backlogIssue = new BacklogIssue($request);
            $this->onWikiCreated($request, $backlogIssue);
            $this->onWikiUpdated($request, $backlogIssue);
            exit;
        }
    }

    private function isWiki(Request $request)
    {
        if($request->input('type') === 5)  return true; // Wikiを追加
        if($request->input('type') === 6)  return true; // Wikiを更新
        if($request->input('type') === 7)  return true; // Wikiを削除

        return false;
    }

    private function isIssue(Request $request)
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
    private function sendIssueToSlack(array $params)
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

    private function sendWikiToSlack(array $params)
    {
        $attachments = array([
            'fallback' => $params['summary'] . ' ' . $params['url'],
            'text'  => $params['url'],
            'color'    => '#ff6600',
            'fields'   => [
                [
                    'title' => 'Status',
                    'value' => $params['summary'],
                    'short' => false,
                ],
                [
                    'title' => 'Content',
                    'value' => $params['diff'],
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

    private function onIssueCreated(Request $request, BacklogIssue $issue)
    {
        if($request->input('type') !== 1) return false;

        $projectName    = $request->input('project.name');
        $projectKey     = $request->input('project.projectKey');
        $keyId          = $request->input('content.key_id');
        $createdUser    = $request->input('createdUser.name');
        $contentSummary = $request->input('content.summary');

        $this->sendIssueToSlack([
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

    private function onIssueUpdated(Request $request, BacklogIssue $issue)
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

        $this->sendIssueToSlack([
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

    private function onWikiCreated(Request $request, BacklogIssue $issue)
    {
        if($request->input('type') !== 5) return false;

        $projectName    = $request->input('project.name');
        $projectKey     = $request->input('project.projectKey');
        $createdUser    = $request->input('createdUser.name');
        $contentName    = $request->input('content.name');

        $this->sendWikiToSlack([
            'summary'   => 'Wiki Created',
            'url'       => "https://{$projectName}.backlog.com/wiki/{$projectKey}/{$contentName}",
            'diff'      => '',
        ]);
        exit;
    }

    private function onWikiUpdated(Request $request, BacklogIssue $issue)
    {
        $type = $request->input('type');
        if($type !== 6 && $type !== 7) return false;

        $projectName    = $request->input('project.name');
        $projectKey     = $request->input('project.projectKey');
        $createdUser    = $request->input('createdUser.name');
        $contentName    = $request->input('content.name');
        $contentDiff    = $request->input('content.diff');

        $this->sendWikiToSlack([
            'summary'   => 'Wiki Updated',
            'url'       => "https://{$projectName}.backlog.com/wiki/{$projectKey}/{$contentName}",
            'diff'      => $contentDiff,
        ]);
        exit;
    }
}
