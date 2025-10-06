<?php

namespace Infrastructure\ExternalServices;

use Domain\Entities\Choice;
use Domain\Entities\GeneratedQuestionsAndChoices;
use Domain\Entities\Question;
use Domain\Repositories\QuestionGeneratorInterface;

class OllamaQuestionGenerator implements QuestionGeneratorInterface {
    public function generateQuestions(string $jobId, int $numQuestions, string $codeSnippet): array {
        $endpoint = 'https://hlumaungphyo.site/api/generate';

        // データの準備
        $data = [
            'model' => 'gpt-oss:latest',
            'prompt' => $codeSnippet,
            'stream' => false
        ];

        // curl セッションの初期化
        $ch = curl_init($endpoint);

        // オプションの設定
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json'
            ]
        ]);

        // リクエストの実行とレスポンスの取得
        $response = curl_exec($ch);

        // エラーハンドリング
        if($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Curl error: ' . $error);
        }

        // Close Curl
        curl_close($ch);

        // レスポンスの処理
        $result = $this->decodeResponse($response, $jobId);

        return $result;
    }


    private function decodeResponse(bool|string $response, $jobId): array {
        $result = json_decode($response, true);

        if(!isset($result['questions'])) {
            throw new \Exception('Ollama API からのレスポンスが不正です。');
        }

        /* 期待されるレスポンス形式の例

            {
            "questions": [
                {
                "question": "What is the purpose of the code snippet?",
                "choices": [
                    { "text": "To connect to a database" },
                    { "text": "To generate questions based on a code snippet" },
                    { "text": "To handle user authentication" },
                    { "text": "To perform data analysis" }
                ],
                "answer": 1
                }
            ]
            }

        */

        $questionsAndChoices = [];
        
        foreach($result['questions'] as $questionIndex => $item) {
            $questionText = $item['question'] ?? "";
            $choices = $item['choices'] ?? [];
            $answerIndex = $item['answer'] ?? null;


            if(empty($questionText) || !is_array($choices)) {
                continue; // Skip Invalid entries
            }

            if(!isset($answerIndex)) {
                throw new \Exception("Ollama API からのレスポンスが不正です。answer index が見つかりません。");
            }

            $answerIndex = (int)($answerIndex);


            // Questions entityを作成
            $questionObj = Question::createNew(
                $jobId,
                $questionText
            );

            $choiceObjs = [];
            // Choicesをループ
            foreach($choices as $index => $choiceItem) {
                $choiceText = $choiceItem['text'] ?? "";
                if($choiceText === "") continue;

                $choiceObjs[] = Choice::createNew(
                    $questionObj->id,
                    $choiceText,
                    ($answerIndex === $index)
                );

            }

            $questionsAndChoices[] = GeneratedQuestionsAndChoices::createNew(
                $questionObj,
                $choiceObjs
            );
        }
        return $questionsAndChoices;
    }
}