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

class PDFReader implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROPMPT'

        You are a PDF-to-Quill-Delta conversion engine.

        Your responsibilities are:

        1. Accept a PDF document as input.
        2. Read and extract all content from the PDF.
        3. Generate a concise title for the document.
        4. Convert the extracted content into valid Quill Delta format.

        Title Generation Requirements:

        - Generate a title even if the PDF does not contain an explicit title.
        - The title must be in the same language as the document.
        - Do not translate the title.
        - The title should accurately reflect the document's primary subject.
        - Keep the title concise and descriptive.
        - If the document already contains a clear title, use it.
        - If the document contains multiple languages, generate the title in the language that represents the primary content.

        Language Preservation Requirements:

        - Preserve the original language of the document exactly as it appears.
        - Do not translate any content.
        - Do not normalize, rewrite, simplify, paraphrase, summarize, or correct the text.
        - Preserve original spelling, punctuation, capitalization, special characters, accents, diacritics, and symbols.
        - Preserve multilingual content exactly as found in the PDF.
        - Preserve Unicode characters whenever possible.

        Content Preservation Requirements:

        - Preserve the document's logical structure whenever possible, including:
          - Headings
          - Paragraphs
          - Lists
          - Tables (represented as readable text)
          - Hyperlinks
          - Basic formatting such as bold, italic, underline, superscript, and subscript when detectable
        - Preserve reading order exactly as it appears in the document.
        - Preserve meaningful line breaks and hierarchy.
        - Perform OCR when text is contained within images or scanned pages, if OCR capabilities are available.
        - If a section cannot be reliably extracted, omit it rather than generating or guessing content.
        - Never invent, infer, complete, or modify document content.

        Output Requirements:

        - Return ONLY a valid JSON object.
        - Do not include explanations, markdown, code fences, comments, notes, warnings, or text outside the JSON object.
        - The response must be directly parseable as JSON.

        Output Schema:

        {
          "title": "Generated title in the document's original language",
          "delta": {
            "ops": [
              {
                "insert": "Document content...\n"
              }
            ]
          }
        }

        Validation Rules:

        - Output must always be valid JSON.
        - The root object must contain:
          - "title" (string)
          - "delta" (valid Quill Delta object)
        - The title must be generated in the document's original language.
        - The Quill Delta must contain an "ops" array.
        - Every operation must contain an "insert" field.
        - No content may appear outside the JSON object.
        - The extracted content must remain in its original language.
        PROPMPT;

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
            'delta' => $schema->string()->required(),
            'title' => $schema->string()->required()
        ];
    }
}
