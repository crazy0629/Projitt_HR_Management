'use client';

import { useEffect, useState } from 'react';
import { usePathname } from 'next/navigation';
import { NotificationsSheet } from '../../partials/topbar/notifications-sheet';
import { UserDropdownMenu } from '../../partials/topbar/user-dropdown-menu';
import {
  Bell,
  CircleQuestionMark,
  Search,
  Menu,
  SquareChevronRight,
} from 'lucide-react';
import { toAbsoluteUrl } from '@/lib/helpers';
import { cn } from '@/lib/utils';
import { useIsMobile } from '@/hooks/use-mobile';
import { useScrollPosition } from '@/hooks/use-scroll-position';
import {
  Sheet,
  SheetBody,
  SheetContent,
  SheetHeader,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Container } from '@/components/common/container';
import { Breadcrumb } from './breadcrumb';
import { Input } from '@/components/ui/input';
import { LanguageDropdown } from '../../partials/topbar/language-dropdown';
import { ChevronDown } from 'lucide-react';
import Link from 'next/link';
import { SidebarMenu } from './sidebar-menu';
import { MegaMenuMobile } from './mega-menu-mobile';

export function Header() {
  const [isSidebarSheetOpen, setIsSidebarSheetOpen] = useState(false);
  const [isMegaMenuSheetOpen, setIsMegaMenuSheetOpen] = useState(false);

  const pathname = usePathname();
  const mobileMode = useIsMobile();

  const scrollPosition = useScrollPosition();
  const headerSticky: boolean = scrollPosition > 0;

  // Close sheet when route changes
  useEffect(() => {
    setIsSidebarSheetOpen(false);
    setIsMegaMenuSheetOpen(false);
  }, [pathname]);

  return (
    <header
      className={cn(
        'header h-[81px] fixed top-0 z-10 start-0 flex items-stretch shrink-0 border-b-[0.6px] border-[#d2d2d2] bg-background end-0 pe-[var(--removed-body-scroll-bar-size,0px)]',
        headerSticky && 'border-b border-border',
      )}
    >
      <Container className="flex justify-between items-stretch lg:gap-4 lg:px-[48px]">
        <div className="flex gap-1 lg:hidden items-center gap-2.5">
          <div className="flex items-center">
            {mobileMode && (
              <Sheet
                open={isSidebarSheetOpen}
                onOpenChange={setIsSidebarSheetOpen}
              >
                <SheetTrigger asChild>
                  <Button variant="ghost" mode="icon">
                    <Menu className="text-muted-foreground/70" />
                  </Button>
                </SheetTrigger>
                <SheetContent
                  className="p-0 gap-0 w-[275px]"
                  side="left"
                  close={false}
                >
                  <SheetHeader className="p-0 space-y-0" />
                  <SheetBody className="p-0 overflow-y-auto">
                    <SidebarMenu />
                  </SheetBody>
                </SheetContent>
              </Sheet>
            )}

          </div>
        </div>
        {pathname.startsWith('/account') ? (
          <Breadcrumb />
        ) : (
          <div className="flex h-full items-center gap-2.5">
            <div className="relative hidden md:block">
              <Search className="size-[18px] text-muted-foreground absolute start-3 top-1/2 -translate-y-1/2" />
              <Input
                placeholder="Search something"
                // value={searchQuery}
                // onChange={(e) => setSearchQuery(e.target.value)}
                className="ps-9 md:w-[278px] "
              />
            </div>
          </div>
        )}

        {/* HeaderTopbar */}
        <div className="flex items-center gap-3">
          <>
            <div className='border-r-[1px] border-[#d9d9d9] pr-[20px] flex gap-[20px]'>
              <LanguageDropdown
                trigger={
                  <div className='flex gap-[10px] items-center cursor-pointer'>
                    <img src="/images/flags/US.png" alt="" className="size-[18px]" />
                    <span>EN</span>
                    <ChevronDown className="size-[18px]! text-[#4b4b4b]" />
                  </div>
                }
              />
              <Button
                variant="ghost"
                mode="icon"
                shape="circle"
                className="size-[24px] hover:bg-primary/10 hover:[&_svg]:text-primary"
              >
                <CircleQuestionMark className="size-[18px]! text-[#4b4b4b]" />
              </Button>

              <NotificationsSheet
                trigger={
                  <Button
                    variant="ghost"
                    mode="icon"
                    shape="circle"
                    className="size-[24px] hover:bg-primary/10 hover:[&_svg]:text-primary"
                  >
                    <Bell className="size-[18px]! text-[#4b4b4b]" />
                  </Button>
                }
              />
            </div>
            <UserDropdownMenu
              trigger={
                <div>
                  <p className='text-[14px]/[18px] text-[#787878]'>Zaid LLC</p>
                  <p className='text-[9px]/[12px] text-[#787878]'>1D: 1232</p>
                </div>
              }
            />
          </>
        </div>
      </Container>
    </header>
  );
}
