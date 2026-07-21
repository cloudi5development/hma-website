<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Seeds the five FAQs from partials/faq.blade.php verbatim. Every page that
 * embeds the accordion showed the same set, so all visibility flags are on.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['q' => 'What courses does Hire Minds Academy offer?', 'a' => 'Our courses span in-demand fields like software development, data & AI, cloud & DevOps, cyber security, and professional skills. Every program is designed by industry experts and blends practical learning, live projects, interview preparation, and dedicated placement support so you graduate genuinely job-ready.'],
            ['q' => 'How long are the training programs?', 'a' => 'Most tracks run between three and six months depending on the depth you choose. We offer flexible weekday and weekend batches along with self-paced modules, so you can learn effectively whether you are a student, a working professional, or switching careers.'],
            ['q' => 'Will I receive placement assistance?', 'a' => 'Yes. Every learner gets end-to-end placement support including resume building, mock interviews, portfolio reviews, and direct referrals to our hiring partners. Our career team stays with you from your very first module until you sign your offer letter.'],
            ['q' => 'Do I receive a course certificate?', 'a' => 'Absolutely. On successful completion of your program and final projects, you receive an industry-recognized certificate from Hire Minds Academy that you can add to your resume and LinkedIn to showcase your verified, job-ready skills to recruiters.'],
            ['q' => 'Can beginners join these courses?', 'a' => 'Definitely. Our programs are structured to take complete beginners from the fundamentals all the way to advanced, real-world skills. With mentor support, hands-on labs, and a friendly community, no prior experience is required to get started.'],
        ];

        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(
                ['question' => $f['q']],
                [
                    'answer'            => $f['a'],
                    'sort_order'        => $i,
                    'is_active'         => true,
                    'show_home'         => true,
                    'show_about'        => true,
                    'show_courses'      => true,
                    'show_testimonials' => true,
                ]
            );
        }
    }
}
