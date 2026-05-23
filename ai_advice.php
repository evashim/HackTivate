<?php
function get_ai_advice($data) {
    /*
      IMPORTANT:
      Replace this with your real API key for demo.
      Do NOT upload your real API key to public GitHub.
    */
    $apiKey = getenv("OPENAI_API_KEY");

    if (!$apiKey) {
        return generate_fallback_advice($data);
    }

    $prompt = "
You are CashCue AI, a friendly financial assistant for young earners in Malaysia.

Based on this financial result, write simple advice in 3 short bullet points.
Do not give investment advice. Do not be too long.
Use simple English.

Financial data:
Decision: {$data['decision']}
Score: {$data['score']}/100
Income: RM{$data['income']}
Basic needs percentage: {$data['essentials_pct']}%
Commitments percentage: {$data['commitments_pct']}%
Savings and goal percentage: {$data['savings_pct']}%
Remaining balance: RM{$data['remaining']}
New commitment name: {$data['commitment_name']}
New monthly amount: RM{$data['commitment_amount']}
Safer new commitment limit: RM{$data['safe_amount']}
Duration: {$data['duration_months']} months
";

    $payload = [
        "model" => "gpt-5.4-mini",
        "input" => $prompt,
        "max_output_tokens" => 220
    ];

    $ch = curl_init("https://api.openai.com/v1/responses");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        return generate_fallback_advice($data);
    }

    $result = json_decode($response, true);

    if (isset($result['output_text'])) {
        return trim($result['output_text']);
    }

    if (isset($result['output'][0]['content'][0]['text'])) {
        return trim($result['output'][0]['content'][0]['text']);
    }

    return generate_fallback_advice($data);
}

function generate_fallback_advice($data) {
    $decision = $data['decision'];
    $commitmentName = $data['commitment_name'];
    $commitmentAmount = number_format((float)$data['commitment_amount'], 2);
    $commitmentsPct = number_format((float)$data['commitments_pct'], 1);
    $safeAmount = number_format((float)$data['safe_amount'], 2);
    $remaining = number_format((float)$data['remaining'], 2);

    if ($decision === "Avoid") {
        return "
• This {$commitmentName} commitment of RM{$commitmentAmount} is not safe for now because your commitments are already {$commitmentsPct}% of your income.
• Your safer new commitment limit is only around RM{$safeAmount}, so RM{$commitmentAmount} may create monthly pressure.
• Try delaying this commitment, reducing the monthly payment, or improving your remaining balance first. Current remaining balance: RM{$remaining}.
";
    }

    if ($decision === "Delay") {
        return "
• This commitment may be possible later, but your current balance is still not strong enough.
• Focus on building savings or reducing existing commitments first.
• Recheck again after your monthly remaining balance becomes more stable.
";
    }

    if ($decision === "Reduce") {
        return "
• You may consider this commitment, but the monthly amount should be reduced.
• Try choosing a cheaper option or longer payment plan.
• Keep your total commitments close to the 30% guide.
";
    }

    return "
• Your financial position looks acceptable, but you should still monitor your monthly balance.
• Make sure this commitment does not disturb your savings goal.
• Keep emergency savings before adding more long-term commitments.
";
}
?>