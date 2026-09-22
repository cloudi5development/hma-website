<?php

namespace Database\Seeders;

use App\Models\PlacementSection;
use Illuminate\Database\Seeder;

/**
 * The Placement Readiness page as it ships: the Placement Success Program copy,
 * section by section.
 *
 * Idempotent — every row is matched on what identifies it (a section by its
 * key, a row by its section, list and place in that list), so running this
 * again restores the shipped wording rather than making a second copy of the
 * page. It is what a fresh install and a live deploy both run.
 */
class PlacementSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $order => $data) {
            $groups = $data['groups'] ?? [];
            unset($data['groups']);

            $section = PlacementSection::updateOrCreate(
                ['key' => $data['key']],
                $data + ['display_order' => $order, 'is_active' => true],
            );

            foreach ($groups as $group => $rows) {
                foreach (array_values($rows) as $index => $row) {
                    $section->items()->updateOrCreate(
                        ['group' => $group, 'display_order' => $index],
                        $row + ['is_active' => true],
                    );
                }
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(): array
    {
        return [
            [
                'key'             => 'hero',
                'label'           => 'Hero',
                'eyebrow'         => 'Recruiter-led campus employability',
                'title'           => 'Placement Success Program',
                'lead'            => 'Diagnose. Develop. Demonstrate. Deploy. A measurable pathway that turns training activity into documented placement readiness.',
                'note'            => 'Built for pre-final and final-year students',
                'primary_label'   => 'Explore the program',
                'primary_url'     => '#placement-framework',
                'secondary_label' => 'Plan a diagnostic',
                'secondary_url'   => '#placement-cta',
                'groups'          => [
                    'point' => [
                        ['title' => 'Individual readiness scorecards'],
                        ['title' => 'Department and batch-level reports'],
                        ['title' => 'Targeted training pathways'],
                        ['title' => 'Complete mock recruitment process'],
                        ['title' => 'Recruiter feedback and placement support'],
                    ],
                ],
            ],
            [
                'key'     => 'challenge',
                'label'   => 'The Challenge',
                'eyebrow' => 'The challenge',
                'title'   => 'Training is completed. But are students ready to clear recruitment?',
                'lead'    => 'Colleges often know who attended training, but not who can successfully navigate aptitude tests, group discussions, technical rounds and HR interviews. HMA closes that visibility gap.',
                'groups'  => [
                    'card' => [
                        ['title' => 'Different students, different gaps', 'text' => 'A single syllabus cannot address every readiness level effectively.'],
                        ['title' => 'Activity without evidence', 'text' => 'Attendance and hours do not reveal improvement in employability.'],
                        ['title' => 'Insufficient simulation', 'text' => 'Students often meet the real selection process before practising it fully.'],
                    ],
                ],
            ],
            [
                'key'     => 'framework',
                'label'   => 'The HMA Framework',
                'eyebrow' => 'The HMA framework',
                'title'   => 'Four stages from diagnosis to opportunity',
                'lead'    => 'Training begins with evidence, responds to gaps and ends with measurable performance.',
                'groups'  => [
                    'stage' => [
                        ['title' => 'Diagnose', 'text' => 'Establish the baseline across aptitude, communication, career profile, interview and role skills.'],
                        ['title' => 'Develop', 'text' => 'Deliver targeted learning aligned to readiness, department and placement goals.'],
                        ['title' => 'Demonstrate', 'text' => 'Simulate tests, GDs and interviews with documented recruiter feedback.'],
                        ['title' => 'Deploy', 'text' => 'Build role-mapped candidate pools and support company-specific preparation.'],
                    ],
                ],
            ],
            [
                'key'     => 'diagnostic',
                'label'   => 'Readiness Diagnostic',
                'eyebrow' => 'Stage 1',
                'title'   => 'HMA Placement Readiness Diagnostic',
                'lead'    => 'Every student receives a score out of 100 across aptitude, reasoning, verbal ability, communication, resume, LinkedIn, interview readiness and role-specific skills.',
                'groups'  => [
                    'band' => [
                        ['title' => 'Placement Ready', 'subtitle' => '80-100', 'text' => 'Company-specific preparation'],
                        ['title' => 'Near Ready', 'subtitle' => '60-79', 'text' => 'Targeted practice and mock hiring'],
                        ['title' => 'Developing', 'subtitle' => '40-59', 'text' => 'Structured skill-building'],
                        ['title' => 'Foundation Required', 'subtitle' => 'Below 40', 'text' => 'Intensive foundational support'],
                    ],
                    'audience' => [
                        ['title' => 'For the student', 'text' => 'Individual scorecard, strengths, gaps and recommended preparation plan.'],
                        ['title' => 'For the department', 'text' => 'Readiness distribution, skill-gap analysis and priority intervention groups.'],
                        ['title' => 'For the institution', 'text' => 'A baseline for training decisions and a post-program improvement report.'],
                    ],
                ],
            ],
            [
                'key'     => 'modules',
                'label'   => 'Training Modules',
                'eyebrow' => 'Stage 2',
                'title'   => 'Targeted training modules',
                'lead'    => 'The final plan is selected according to diagnostic findings, departments and institutional placement priorities.',
                'groups'  => [
                    'module' => [
                        ['title' => 'Aptitude & Assessment Readiness', 'text' => 'Quantitative aptitude, reasoning, data interpretation, verbal ability, speed, accuracy and company-pattern tests.'],
                        ['title' => 'Communication & Professional Skills', 'text' => 'Self-introduction, group discussion, presentations, workplace communication, etiquette, confidence and teamwork.'],
                        ['title' => 'Resume & LinkedIn Readiness', 'text' => 'JD analysis, ATS-compatible resumes, project presentation, achievement writing, LinkedIn and networking.'],
                        ['title' => 'Interview Preparation', 'text' => 'HR and competency rounds, STAR answers, technical communication, difficult questions, virtual interviews and body language.'],
                        ['title' => 'Technical & Role Readiness', 'text' => 'Department-specific pathways covering technology, analytics, business, HR, finance, sales and operations.'],
                        ['title' => 'Company-Specific Preparation', 'text' => "Focused practice aligned to an upcoming employer's assessment pattern, role profile and selection stages."],
                    ],
                ],
            ],
            [
                'key'     => 'mock',
                'label'   => 'Mock Recruitment',
                'eyebrow' => 'Stages 3 and 4',
                'title'   => 'Mock recruitment and placement support',
                'lead'    => 'Students demonstrate readiness through a realistic selection process and receive structured feedback.',
                'note'    => 'A responsible promise: HMA provides training, assessment and placement assistance. Employment outcomes depend on student performance, employer criteria and opportunity availability.',
                'groups'  => [
                    'step' => [
                        ['title' => 'Resume screening'],
                        ['title' => 'Aptitude & verbal test'],
                        ['title' => 'Group discussion'],
                        ['title' => 'Technical / functional round'],
                        ['title' => 'HR interview'],
                    ],
                    'outcome' => [
                        ['title' => 'Student outcomes', 'text' => 'Stage-wise ratings, strengths, improvement areas, recruiter feedback and a final readiness classification.'],
                        ['title' => 'Placement support', 'text' => 'Candidate mapping, employer sessions, company-specific preparation, opportunity sharing, interview coordination and relevant referrals where available.'],
                    ],
                ],
            ],
            [
                'key'     => 'formats',
                'label'   => 'Engagement Formats',
                'eyebrow' => 'Flexible engagement',
                'title'   => 'Choose the depth that matches your placement calendar',
                'groups'  => [
                    'format' => [
                        ['title' => 'Readiness Diagnostic', 'subtitle' => '75-90 minutes', 'text' => 'Identifying current gaps before intervention'],
                        ['title' => 'Placement Foundation', 'subtitle' => '24-30 hours', 'text' => 'Immediate essential preparation'],
                        ['title' => 'Placement Accelerator', 'subtitle' => '45-60 hours', 'text' => 'Intensive final-year preparation'],
                        ['title' => 'Placement Success Program', 'subtitle' => '90-120 hours', 'text' => 'Complete placement-development cycle'],
                        ['title' => 'Company-Specific Sprint', 'subtitle' => '6-15 hours', 'text' => 'Preparation for an upcoming drive'],
                    ],
                ],
            ],
            [
                'key'     => 'why',
                'label'   => 'Why HireMinds Academy',
                'eyebrow' => 'Why HireMinds Academy',
                'title'   => 'Recruitment understanding built into the program',
                'groups'  => [
                    'feature' => [
                        ['title' => 'Recruiter-led curriculum', 'text' => 'Content reflects real screening, interview and selection expectations.'],
                        ['title' => 'Evidence-led intervention', 'text' => 'The diagnostic defines the training need before hours are committed.'],
                        ['title' => 'Actionable reporting', 'text' => 'Student feedback and management reports support better decisions.'],
                        ['title' => 'Complete simulation', 'text' => 'Students practise the end-to-end recruitment journey.'],
                        ['title' => 'Flexible delivery', 'text' => 'Short, intensive and semester-based models fit academic schedules.'],
                        ['title' => 'Recruitment ecosystem', 'text' => 'Supported by Culminant Executive Services, established in 2013.'],
                    ],
                ],
            ],
            [
                'key'           => 'cta',
                'label'         => 'CTA / Contact',
                'eyebrow'       => 'Recommended first step',
                'title'         => 'Begin with one final-year batch.',
                'lead'          => 'We will assess current readiness, share a consolidated gap report and recommend the exact intervention required. Scope, schedule and commercials are customised to the institution.',
                'primary_label' => 'Talk to our team',
                'primary_url'   => '/contact-us',
                'phone'         => '+91 78240 94044',
                'email'         => 'info@hiremindsacademy.com',
            ],
        ];
    }
}
