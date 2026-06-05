<?php

namespace App\Services;

use App\Ai\Agents\Summarizer;
use App\Events\NoteSummarizationFailed;
use App\Events\NoteSummarized;
use App\Models\Note;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class SummaryService
{
    public function summarize(array $content, string $title, string $userId, ?string $spaceId)
    {
        $agent = new Summarizer;
        $agent->queue(prompt: 'Summarize This note: ' . json_encode($content))
            ->then(function (AgentResponse $response) use ($title, $userId, $spaceId) {
                $summary = Note::create([
                    "title" => $title . " (Summarized)",
                    "content" => json_decode($response->structured["value"])->ops,
                    "user_id" => $userId,
                    "space_id" => $spaceId
                ]);

                NoteSummarized::dispatch($userId, $summary);

            })->catch(function (Throwable $e) use ($userId, $title) {
                logger()->error('Summarization failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                NoteSummarizationFailed::dispatch($userId, 'Summarization failed, please try again.', $title);
            });

    }
}
