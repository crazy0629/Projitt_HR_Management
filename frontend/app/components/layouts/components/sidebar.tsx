'use client';

import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { useSettings } from '@/providers/settings-provider';
import { SidebarHeader } from './sidebar-header';
import { SidebarMenu } from './sidebar-menu';
import { ChevronsUpDown, LogOut } from 'lucide-react';
import { logout } from '@/api/user';
import { useSession } from '@/context/SessionContext';
import { useRouter } from 'next/navigation';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export function Sidebar() {
  const { settings } = useSettings();
  const pathname = usePathname();
  const { setSession } = useSession();
  const router = useRouter();
  const handleLogout = async () => {
    const response = await logout()
    if (response.data.status === true) {
      setSession({ token: "", authenticated: false })
      router.push('/signin')
    }
  }
  return (
    <div
      className={cn(
        'sidebar flex flex-col justify-between bg-background lg:shadow-[1px_0px_4px_0px_rgba(0,0,0,0.06)] lg:fixed lg:top-0 lg:bottom-0 lg:z-20 lg:flex  items-stretch shrink-0',

      )}
    >
      <div>
        <SidebarHeader />
        <div className="overflow-hidden">
          <SidebarMenu />
        </div>
      </div>
      {settings.layouts.demo1.sidebarCollapse ?
        <div className='w-full flex justify-center mb-[25px]'>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <img src="/images/photo.png" alt="" className='w-[32px] h-[32px] rounded-full' />
            </DropdownMenuTrigger>
            <DropdownMenuContent side="bottom" align="end">
              <div className="cursor-pointer hover:bg-[#e9e9e9] text-[14px]/[20px] py-[7px] px-[12px] rounded-[8px] flex items-center gap-[12px]" onClick={handleLogout}>
                <LogOut className='size-[20px]' />
                Logout
              </div>
            </DropdownMenuContent>
          </DropdownMenu>

        </div>
        : <div className='pl-[22px] pr-[19px] mb-[25px]'>

          <div className='w-full rounded-[8px] bg-[#f9f9f9] py-[14px] px-[12px] flex items-center justify-between'>
            <div className='flex items-center gap-[12px]'>
              <img src="/images/photo.png" alt="" className='w-[32px] h-[32px] rounded-full' />
              <div>
                <p className='font-medium text-[#4b4b4b] text-[14px]/[20px]'>Abubar Ali</p>
                <p className='text-[#a5a5a5] text-[12px]/[15px]'>HR Management</p>
              </div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button className="size-7 cursor-pointer">
                  <ChevronsUpDown className='size-[20px]' />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent side="bottom" align="end">
                <div className="cursor-pointer hover:bg-[#e9e9e9] text-[14px]/[20px] py-[7px] px-[12px] rounded-[8px] flex items-center gap-[12px]" onClick={handleLogout}>
                  <LogOut className='size-[20px]' />
                  Logout
                </div>
              </DropdownMenuContent>
            </DropdownMenu>

          </div>
        </div>}
    </div>
  );
}
