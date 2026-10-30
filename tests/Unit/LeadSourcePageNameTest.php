<?php

namespace Tests\Unit;

use App\Models\Lead;
use PHPUnit\Framework\TestCase;

class LeadSourcePageNameTest extends TestCase
{
    public function test_facebook_form_name_is_used_as_the_source_page_name(): void
    {
        $pageName = Lead::resolveSourcePageName(
            'Facebook Lead Ads | Form: Forest Walk Diwali 2026 | Campaign: Diwali',
            'https://www.facebook.com',
            '360 PropGuide'
        );

        $this->assertSame('Forest Walk Diwali 2026', $pageName);
    }

    public function test_non_facebook_source_keeps_its_existing_page_name(): void
    {
        $pageName = Lead::resolveSourcePageName(
            'Facebook Lead Ads | Form: Forest Walk Diwali 2026',
            '360propguide.com',
            '360 PropGuide'
        );

        $this->assertSame('360 PropGuide', $pageName);
    }

    public function test_missing_facebook_form_name_keeps_its_fallback(): void
    {
        $pageName = Lead::resolveSourcePageName(
            'Facebook Lead Ads',
            'facebook.com',
            '360 PropGuide'
        );

        $this->assertSame('360 PropGuide', $pageName);
    }

    public function test_facebook_form_page_name_is_not_reformatted(): void
    {
        $lead = new Lead([
            'source_website' => 'facebook.com',
            'source_page_name' => 'FE_GNW_QualifiedBuyer_V2',
        ]);

        $this->assertSame('FE_GNW_QualifiedBuyer_V2', $lead->page_name);
    }
}
