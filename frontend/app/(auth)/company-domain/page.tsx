'use client';

/**
 * Company Domain Hint Page
 *
 * This page helps users identify their Projitt subdomain. 
 * It gives a hint about the URL format and redirects back to domain input when ready.
 */

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Eye, EyeOff } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { LoaderCircleIcon } from 'lucide-react';

export default function Page() {
  const router = useRouter();

  return (
    <>
      <div
        className="absolute w-full h-full overflow-hidden"
        id="blur-background"
        data-testid="blur-background"
      >
        <div
          className="w-[618px] h-[618px] bg-[#0d978b] top-[379px] left-[769px] opacity-[10%] rounded-full absolute z-[2] blur-[100px]"
          id="bg-circle-green"
          data-testid="bg-circle-green"
        ></div>
        <div
          className="w-[483px] h-[483px] bg-[#ffa750] top-[660px] left-[264px] opacity-[10%] rounded-full absolute z-[2] blur-[100px]"
          id="bg-circle-orange"
          data-testid="bg-circle-orange"
        ></div>
      </div>

      <div className="h-full overflow-y-auto" id="page-scroll-area">
        <div className="w-full h-full flex flex-col justify-between">
          {/* Top Section */}
          <div
            className="mt-[60px] flex flex-col items-center w-full relative z-[3] px-[10px]"
            id="page-header"
            data-testid="page-header"
          >
            <img
              src="/images/logo.png"
              alt="Projitt logo"
              className="h-[52px]"
              id="logo-image"
              data-testid="logo-image"
            />

            <div
              className="sm:w-[482px] w-full border border-[#e9e9e9] rounded-[16px] bg-white pt-[48px] px-[38px] mt-[90px]"
              id="domain-helper-card"
              data-testid="domain-helper-card"
            >
              <div className="mb-0">
                <h1
                  className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535]"
                  id="domain-helper-title"
                  data-testid="domain-helper-title"
                >
                  What’s my company’s Projitt domain?
                </h1>
              </div>

              <Input
                disabled
                value="https://company.projitt.com"
                className="h-[48px] mt-[14px]"
                id="domain-sample-input"
                data-testid="domain-sample-input"
              />

              <p
                className="text-[14px]/[20px] text-[#626262] mt-[20px]"
                id="domain-description"
                data-testid="domain-description"
              >
                Check your browser’s address bar when logged in to projitt or ask a colleague.
                The text just before <code>.projitt.com</code> is your company domain
              </p>

              <Button
                className="h-[48px] w-full text-[14px]/[20px] font-semibold mt-[30px]"
                onClick={() => router.push('/input-domain')}
                id="go-back-button"
                data-testid="go-back-button"
              >
                Thanks, Go Back
              </Button>

              <p
                className="text-[14px]/[20px] text-[#626262] mt-[20px] text-center mb-[46px]"
                id="signup-redirect"
                data-testid="signup-redirect"
              >
                New here?{' '}
                <Link
                  href="/signup"
                  className="font-semibold text-[#0D978B]"
                  id="create-account-link"
                  data-testid="create-account-link"
                >
                  Create an account
                </Link>
              </p>
            </div>
          </div>

          {/* Footer */}
          <div
            className="w-full pb-[50px] pl-[80px] pr-[80px] flex md:flex-row flex-col items-center justify-between gap-[10px] md:mt-0 mt-[10px]"
            id="footer-section"
            data-testid="footer-section"
          >
            <div className="flex gap-[16px]" id="footer-links" data-testid="footer-links">
              <span
                className="text-[14px]/[22px] underline text-[#a19e9e]"
                id="footer-terms"
                data-testid="footer-terms"
              >
                Terms of Service
              </span>
              <div className="w-[1px] h-[20px] bg-[#a19e9e]" />
              <span
                className="text-[14px]/[22px] underline text-[#a19e9e]"
                id="footer-privacy"
                data-testid="footer-privacy"
              >
                Privacy Policy
              </span>
            </div>

            <span
              className="text-[14px]/[22px] text-[#a19e9e]"
              id="footer-copyright"
              data-testid="footer-copyright"
            >
              © 2025 Projitt
            </span>
          </div>
        </div>
      </div>
    </>
  );
}
