<?php

namespace Database\Seeders;

use App\Models\ChatHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    /** @var list<string> */
    private array $userMessages = [
        'Saya merasa sangat lelah akhir-akhir ini.',
        'Sulit tidur beberapa hari terakhir.',
        'Saya tidak bisa berkonsentrasi dengan baik.',
        'Perasaan saya campur aduk.',
        'Saya merasa lebih baik hari ini.',
        'Kadang saya merasa tidak berguna.',
        'Saya ingin berbicara dengan dokter.',
        'Bisakah kamu membantu saya?',
        'Saya merasa cemas dengan pekerjaan.',
        'Hubungan saya dengan keluarga sedang tidak baik.',
        'Saya sering merasa sedih tanpa alasan yang jelas.',
        'Hari ini terasa sangat berat.',
        'Saya mencoba teknik relaksasi yang kamu sarankan.',
        'Saya tidak tahu harus mulai dari mana.',
        'Perasaan saya jauh lebih stabil sekarang.',
    ];

    /** @var list<string> */
    private array $aiMessages = [
        'Terima kasih sudah berbagi perasaanmu. Saya di sini untuk mendengarkan.',
        'Itu terdengar sangat sulit. Mari kita cari cara untuk membantumu.',
        'Apakah ada hal tertentu yang membuatmu merasa seperti itu?',
        'Saya memahami perasaanmu. Istirahat yang cukup sangat penting.',
        'Bagaimana tidurmu malam ini?',
        'Teknik pernapasan dalam bisa sangat membantu untuk relaksasi.',
        'Saya senang mendengar kamu merasa lebih baik hari ini!',
        'Mari kita bicarakan lebih lanjut tentang apa yang kamu rasakan.',
        'Kamu sudah melakukan hal yang benar dengan mau berbagi.',
        'Ingat, kamu tidak harus menghadapi ini sendirian.',
    ];

    /**
     * Seed 10–20 chat messages per patient (alternating user / ai).
     */
    public function run(): void
    {
        $patients  = User::where('role', 'patient')->pluck('id');
        $chunk     = [];
        $timestamp = now()->toDateTimeString();

        foreach ($patients as $userId) {
            $total      = mt_rand(10, 20);
            $senderType = 'user'; // Start with user message

            for ($i = 0; $i < $total; $i++) {
                $message        = $senderType === 'user'
                    ? $this->userMessages[array_rand($this->userMessages)]
                    : $this->aiMessages[array_rand($this->aiMessages)];
                $sentimentScore = $senderType === 'user'
                    ? round(mt_rand(-1000, 1000) / 1000, 3)
                    : null;
                $detectedEmotion = $senderType === 'user' && mt_rand(0, 1)
                    ? ['calm', 'anxious', 'sad', 'angry', 'neutral'][mt_rand(0, 4)]
                    : null;

                $chunk[] = [
                    'uuid'             => Str::uuid(),
                    'user_id'          => $userId,
                    'message'          => $message,
                    'sender_type'      => $senderType,
                    'sentiment_score'  => $sentimentScore,
                    'detected_emotion' => $detectedEmotion,
                    'context_data'     => null,
                    'is_flagged'       => false,
                    'created_at'       => now()->subDays(mt_rand(0, 29))->subMinutes(mt_rand(0, 1440))->toDateTimeString(),
                    'updated_at'       => $timestamp,
                ];

                // Alternate sender
                $senderType = $senderType === 'user' ? 'ai' : 'user';
            }
        }

        foreach (array_chunk($chunk, 500) as $batch) {
            DB::table('chat_histories')->insert($batch);
        }
    }
}

