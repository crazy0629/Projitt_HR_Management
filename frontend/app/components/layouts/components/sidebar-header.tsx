'use client';

import Link from 'next/link';
import { ArrowLeftToLine, ArrowRightToLine } from 'lucide-react';
import { toAbsoluteUrl } from '@/lib/helpers';
import { cn } from '@/lib/utils';
import { useSettings } from '@/providers/settings-provider';
import { Button } from '@/components/ui/button';

export function SidebarHeader() {
  const { settings, storeOption } = useSettings();

  const handleToggleClick = () => {
    storeOption(
      'layouts.demo1.sidebarCollapse',
      !settings.layouts.demo1.sidebarCollapse,
    );
  };

  return (
    <div className={`pt-[28px] hidden lg:flex items-center relative justify-between lg:pl-[32px] shrink-0 ${settings.layouts.demo1.sidebarCollapse ? 'lg:pr-[32px]' : 'lg:pr-[16px] '}`}>
      <Link href="/">
        <div className="dark:hidden">
          <img
            src="/images/logo.png"
            className="default-logo h-[22px] max-w-none"
            alt="Default Logo"
          />
        </div>
        <div className="hidden dark:block">
          <img
            src="/images/logo.png"
            className="default-logo h-[32px] max-w-none"
            alt="Default Dark Logo"
          />

        </div>
      </Link>
      <button
        onClick={handleToggleClick}
        className="cursor-pointer"
      >
        {!settings.layouts.demo1.sidebarCollapse ? <ArrowLeftToLine className="size-[18px] text-[#A5A5A5] hover:text-[#0d978b]" /> : <ArrowRightToLine className="size-[18px] text-[#A5A5A5] hover:text-[#0d978b]" />}
      </button>
    </div>
  );
}
