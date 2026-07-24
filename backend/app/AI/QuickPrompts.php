<?php

namespace App\AI;

class QuickPrompts
{
    public static function all(): array
    {
        return [
            'performance' => [
                'Summarize today\'s performance',
                'Why did traffic drop?',
                'Why did conversions decrease?',
                'Find unusual changes',
                'Compare this month to last month',
            ],
            'campaigns' => [
                'Show my best campaigns',
                'How can I improve ROAS?',
                'Suggest better keywords',
            ],
            'seo' => [
                'How can I improve SEO?',
                'Suggest local SEO improvements',
                'Suggest technical SEO improvements',
            ],
            'content' => [
                'Generate content ideas',
                'Suggest blog topics',
                'Generate ad headlines',
                'Suggest social media captions',
            ],
            'strategy' => [
                'Recommend next marketing steps',
                'Generate a marketing strategy',
                'Generate an executive summary',
                'Generate a client report',
            ],
        ];
    }
}
