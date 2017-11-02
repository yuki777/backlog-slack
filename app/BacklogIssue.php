<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BacklogIssue extends Model
{
    public function __construct(Request $request)
    {
        $result = $this->curlGet($this->getUrl($request));
        foreach($result as $k => $v){
            $this[$k] = $v;
        }
    }

    private function curlGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $result = curl_exec($ch);

        if(curl_error($ch)){
            \Log::info(curl_error($ch));
            return false;
        }

        curl_close($ch);

        $result = json_decode($result, true);

        return $result;
    }

    private function getUrl(Request $request)
    {
        $apiKey      = getenv('BACKLOG_API_KEY');
        $projectName = $request->input('project.name');
        $projectKey  = $request->input('project.projectKey');
        $keyId       = $request->input('content.key_id');
        $url         = "https://{$projectName}.backlog.com/api/v2/issues/{$projectKey}-{$keyId}?apiKey={$apiKey}";

        return $url;
    }
}
