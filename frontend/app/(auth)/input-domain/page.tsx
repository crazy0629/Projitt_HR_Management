'use client';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Form, FormField, FormItem, FormControl, FormMessage } from '@/components/ui/form';
import { getDomainSchema, DomainSchemaType } from '@/app/(auth)/forms/domain-schema';
import { useRouter } from 'next/navigation';
import { JSX } from 'react';

/**
 * InputDomain component renders a form for entering a company domain
 * to login. It validates the input domain using a Zod schema,
 * and on submission navigates to the signin page with the domain as a query parameter.
 *
 * @returns JSX.Element - The domain input form UI
 */
export default function InputDomain(): JSX.Element {
  const router = useRouter();

  const form = useForm<DomainSchemaType>({
    resolver: zodResolver(getDomainSchema()),
    defaultValues: {
      domain: '',
    },
  });

  /**
   * Handles form submission by redirecting to signin page with the domain query.
   *
   * @param values - The form values containing the domain
   * @returns void
   */
  function onSubmit(values: DomainSchemaType): void {
    router.push(`/signin?domain=${values.domain}`);
  }

  return (
    <>
      {/* Background blurred circles */}
      <div className="absolute w-full h-full overflow-hidden">
        <div
          id="bg-circle-teal"
          className="w-[618px] h-[618px] bg-[#0d978b] top-[379px] left-[769px] opacity-[10%] rounded-full absolute z-[2] blur-[100px]"
        ></div>
        <div
          id="bg-circle-orange"
          className="w-[483px] h-[483px] bg-[#ffa750] top-[660px] left-[264px] opacity-[10%] rounded-full absolute z-[2] blur-[100px]"
        ></div>
      </div>

      <div className="h-full overflow-y-auto">
        <div className="w-full h-full flex flex-col justify-between">
          {/* Form container */}
          <div className="mt-[60px] flex flex-col items-center w-full relative z-[3] px-[10px]">
            <img src="/images/logo.png" alt="logo" className="h-[52px]" id="projitt-logo" />

            <div
              id="domain-form-container"
              className="sm:w-[482px] w-full border border-[#e9e9e9] rounded-[16px] bg-white pt-[48px] px-[38px] mt-[90px]"
            >
              <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="block w-full space-y-5" data-testid="domain-form">
                  <p
                    id="domain-form-header"
                    className="font-semibold text-[22px]/[30px] text-[#353535]"
                  >
                    Enter your Projitt Domain to login
                  </p>

                  <FormField
                    control={form.control}
                    name="domain"
                    render={({ field }) => (
                      <FormItem>
                        <FormControl>
                          <div className="relative">
                            <Input
                              id="input-domain"
                              data-testid="input-domain"
                              placeholder="Company Domain"
                              {...field}
                              className="h-[48px] pl-[10px]"
                            />
                            <span
                              id="domain-suffix"
                              className="absolute top-[50%] right-[16px] -translate-y-[50%] text-[14px]/[20px] text-[#353535]"
                            >
                              .projitt.com
                            </span>
                          </div>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <p
                    id="whats-my-domain-link"
                    data-testid="whats-my-domain-link"
                    className="text-[14px]/[20px] font-[500] text-[#626262] mt-[12px] text-right cursor-pointer"
                    onClick={() => router.push('/company-domain')}
                  >
                    What’s my domain?
                  </p>

                  <Button
                    id="continue-button"
                    data-testid="continue-button"
                    type="submit"
                    className="mt-[24px] w-full h-[48px]"
                  >
                    <span className="text-[14px] text-white font-semibold">Continue</span>
                  </Button>

                  <p
                    id="create-account-text"
                    className="text-[14px]/[20px] text-[#626262] mt-[20px] text-center mb-[46px]"
                  >
                    New here?{' '}
                    <Link
                      id="signup-link"
                      href="/signup"
                      className="font-semibold text-[#0D978B]"
                      data-testid="signup-link"
                    >
                      Create an account
                    </Link>
                  </p>
                </form>
              </Form>
            </div>
          </div>

          {/* Footer with links */}
          <div
            id="footer"
            className="w-full pb-[50px] pl-[80px] pr-[80px] flex md:flex-row flex-col items-center justify-between gap-[10px] md:mt-0 mt-[10px]"
          >
            <div className="flex gap-[16px]">
              <span
                id="terms-of-service"
                className="text-[14px]/[22px] underline text-[#a19e9e]"
                data-testid="terms-of-service"
              >
                Terms of Service
              </span>
              <div className="w-[1px] h-[20px] bg-[#a19e9e]" />
              <span
                id="privacy-policy"
                className="text-[14px]/[22px] underline text-[#a19e9e]"
                data-testid="privacy-policy"
              >
                Privacy Policy
              </span>
            </div>

            <span id="copyright" className="text-[14px]/[22px] text-[#a19e9e]">
              © 2025 Projitt
            </span>
          </div>
        </div>
      </div>
    </>
  );
}
