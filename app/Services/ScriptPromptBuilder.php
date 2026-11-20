<?php

namespace App\Services;

class ScriptPromptBuilder
{
    /**
     * This is the main method.
     * It takes all the user's form inputs and builds one big text prompt.
     * That prompt is then sent to GeminiService to generate the script.
     *
     * @param  array<string, mixed>      $inputData          All form fields (audience, product, etc.)
     * @param  array<string, mixed>|null $brandProfile       Brand profile snapshot (optional)
     * @param  array<string, mixed>|null $customTone         Custom tone snapshot (optional)
     * @return string                                        The final prompt text
     */
    public function build(
        array $inputData,
        ?array $brandProfile,
        ?array $customTone,
    ): string {
        // We build each part of the prompt separately,
        // then join them together at the end.
        $parts = [];

        // 1. Tell the AI what its role is
        $parts[] = $this->buildRoleInstruction();

        // 2. Add the script configuration (type, platform, tone, etc.)
        $parts[] = $this->buildConfiguration($inputData);

        // 3. Add the product and audience details
        $parts[] = $this->buildContentDetails($inputData);

        // 4. Add brand profile info (only if the user selected one)
        if (! empty($brandProfile)) {
            $parts[] = $this->buildBrandProfileSection($brandProfile);
        }

        // 5. Add custom tone sample (only if the user selected one)
        if (! empty($customTone)) {
            $parts[] = $this->buildCustomToneSection($customTone);
        }

        // 6. Tell the AI exactly what format to return
        $parts[] = $this->buildOutputFormat();

        // Join all parts with a blank line between each section
        return implode("\n\n", $parts);
    }

    /**
     * Tells the AI what role to play.
     * This sets the AI's behaviour for the whole response.
     */
    private function buildRoleInstruction(): string
    {
        return 'You are a professional script writer who creates engaging short-form video scripts. '
            . 'Write a complete, ready-to-use script based on the details below. '
            . 'Do not explain your process — just return the finished script.';
    }

    /**
     * Builds the configuration section.
     * This includes: script type, platform, tone, language, and duration.
     *
     * @param array<string, mixed> $inputData
     */
    private function buildConfiguration(array $inputData): string
    {
        // We use the null coalescing operator (??) to safely get values.
        // If a value is missing, we fall back to 'N/A'.
        $lines = [
            '--- Script Configuration ---',
            'Script Type : ' . ($inputData['script_type'] ?? 'N/A'),
            'Platform    : ' . ($inputData['platform']    ?? 'N/A'),
            'Tone        : ' . ($inputData['tone']        ?? 'N/A'),
            'Language    : ' . ($inputData['language']    ?? 'N/A'),
            'Duration    : ' . ($inputData['duration']    ?? 'N/A') . ' seconds',
        ];

        // implode() joins an array into a single string, here with a newline between each line
        return implode("\n", $lines);
    }

    /**
     * Builds the product and audience details section.
     * This is the core information about what the script is promoting.
     *
     * @param array<string, mixed> $inputData
     */
    private function buildContentDetails(array $inputData): string
    {
        $lines = [
            '--- Content Details ---',
            'Target Audience  : ' . ($inputData['target_audience']      ?? 'N/A'),
            'Audience Pain Points: ' . ($inputData['pain_points']       ?? 'Not provided'),
            'Product / Service: ' . ($inputData['product_name']         ?? 'N/A'),
            'Description      : ' . ($inputData['product_description']  ?? 'N/A'),
            'Key Benefits     : ' . ($inputData['product_benefits']     ?? 'Not provided'),
            'Content Objective: ' . ($inputData['objective']            ?? 'N/A'),
           
        ]; 

        // Only add "Additional Instructions" if the user actually typed something
        if (! empty($inputData['additional_instructions'])) {
            $lines[] = 'Additional Instructions: ' . $inputData['additional_instructions'];
        }

        return implode("\n", $lines);
    }

    /**
     * Builds the brand profile section.
     * This gives the AI extra context about the user's brand style.
     * We treat this as reference data only — not as AI instructions.
     *
     * @param array<string, mixed> $brandProfile
     */
    private function buildBrandProfileSection(array $brandProfile): string
    {
        $lines = [
            '--- Brand Profile (use as style reference only) ---',
            'Brand Name    : ' . ($brandProfile['brand_name']             ?? 'N/A'),
            'Writing Style : ' . ($brandProfile['writing_style']          ?? 'N/A'),
            'Personality   : ' . ($brandProfile['personality']            ?? 'N/A'),
            'Preferred Words: ' . ($brandProfile['preferred_words']       ?? 'N/A'),
            'Avoid Words   : ' . ($brandProfile['avoid_words']            ?? 'N/A'),
            'Emoji Usage   : ' . ($brandProfile['emoji_usage']            ?? 'N/A'),
        ];

        return implode("\n", $lines);
    }

    /**
     * Builds the custom tone section.
     * This gives the AI a sample of how the user wants the content to sound.
     * We treat this as reference data only — not as AI instructions.
     *
     * @param array<string, mixed> $customTone
     */
    private function buildCustomToneSection(array $customTone): string
    {
        $lines = [
            '--- Custom Tone Sample (use as style reference only) ---',
            'Tone Name   : ' . ($customTone['name']           ?? 'N/A'),
            'Description : ' . ($customTone['description']    ?? 'N/A'),
            'Sample Text : ' . ($customTone['sample_content'] ?? 'N/A'),
        ];

        return implode("\n", $lines);
    }

    /**
     * Tells the AI exactly what format to return.
     * This is very important — it makes the response structured and predictable,
     * so we can parse and display each section in the UI easily.
     */
    private function buildOutputFormat(): string
    {
        return implode("\n", [
            '--- Required Output Format (follow exactly) ---',
            'Return the script in this exact format with these exact labels:',
            '',
            'Title: [script title here]',
            'Hook: [the opening line to grab attention]',
            'Main Content: [the body of the script]',
            'CTA: [the call-to-action at the end]',
            'Estimated Duration: [e.g. 30 seconds]',
            'Language: [the language used]',
        ]);
    }
}
