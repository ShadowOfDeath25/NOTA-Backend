<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class Summarizer implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are an assistant that processes notes.

        Input:
        - The input may be in Quill Delta format OR plain text.
        - You must correctly interpret both formats as source content for summarization.

        Task:
        - Summarize the content of the note concisely.
        - Always return the result in valid Quill Delta format, regardless of input format.

        Output rules (strict):
        - Output MUST be valid Quill Delta JSON only.
        - Do NOT include explanations, markdown, or any extra text.
        - The output must follow Quill Delta structure using an "ops" array.
        - Never output plain text.

        Content rules:
        - Preserve the original language of the input note (do not translate).
        - Do not add any information not present in the source.
        - Summarize in a clear, concise, information-dense way.
        - If the input is already short, slightly compress it while preserving meaning.

        Styling rules (use sparingly and appropriately):
        - Use bold for key headings or main ideas when helpful.
        - Use italics for emphasis only when it improves clarity.
        - Do not over-format or add unnecessary styling.

        Output format example:
        {
          "ops": [
            { "insert": "Summary title\n", "attributes": { "bold": true } },
            { "insert": "Concise summary of the note in the same language.\n" }
          ]
        }
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
        ];
    }
}
