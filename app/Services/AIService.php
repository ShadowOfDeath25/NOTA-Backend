<?php

namespace App\Services;

use App\Ai\Agents\PDFReader;
use App\Ai\Agents\Summarizer;
use App\Events\NoteSummarizationFailed;
use App\Events\NoteSummarized;
use App\Events\PDFExtracted;
use App\Events\PDFExtractionFailed;
use App\Models\Note;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class AIService
{
    public function summarize(array $content, string $title, string $userId, ?string $spaceId): void
    {
        $agent = new Summarizer;
        $agent->queue(prompt: 'Summarize This note: '.json_encode($content))
            ->then(function (AgentResponse $response) use ($title, $userId, $spaceId) {
                $summary = Note::create([
                    'title' => $title.' (Summarized)',
                    'content' => json_decode($response->structured['value'])->ops,
                    'user_id' => $userId,
                    'space_id' => $spaceId,
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

    public function extractPDF($file, string $userId, ?string $spaceId): void
    {
        $agent = new PDFReader;
        $agent->queue(prompt: 'Read the text from this PDF', attachments: [Document::fromUpload($file)])
            ->then(function (AgentResponse $response) use ($userId, $spaceId) {
                $note = Note::create([
                    'title' => $response->structured['title'],
                    'content' => json_decode($response->structured['delta']->ops),
                    'user_id' => $userId,
                    'space_id' => $spaceId,
                ]);

                PDFExtracted::dispatch($userId, $note);
            })->catch(function (Throwable $e) use ($userId) {
                logger()->error('Extraction Failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                PDFExtractionFailed::dispatch($userId);
            });
    }
}
