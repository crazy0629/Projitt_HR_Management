/**
 * Signup Page Component
 *
 * This page provides the user interface and logic for user registration (sign up) and email verification.
 * It includes a form for collecting user details, a phone number selector, and a verification code input.
 * The component manages UI state for form submission, error handling, and navigation.
 *
 * UI elements are provided with unique locators (id, data-testid) to aid UI test automation.
 */
'use client';

import { Suspense, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { zodResolver } from '@hookform/resolvers/zod';
import { Check } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { Alert, AlertIcon, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import phoneNumber from '@/constants/phoneNumber.json';
import PinField from 'react-pin-field';
import { JSX } from 'react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
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
import { getSignupSchema, SignupSchemaType } from '../forms/signup-schema';
import { getVerifyCodeSchema, VerifyCodeSchemaType } from '../forms/verify-code-schema';

/**
 * Signup Page React Component
 *
 * @returns {JSX.Element} The signup page UI
 */
export default function Page(): JSX.Element {
  const router = useRouter();
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [passwordConfirmationVisible, setPasswordConfirmationVisible] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<boolean | null>(false);
  const [showRecaptcha, setShowRecaptcha] = useState(false);
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [checkEmail, setCheckEmail] = useState(false);

  const form = useForm<SignupSchemaType>({
    resolver: zodResolver(getSignupSchema()),
    defaultValues: {
      email: '',
      companyName: '',
      firstName: '',
      lastName: '',
      country: 'us',
      phoneNumber: '',
      industry: '',
      companySize: 'small',
      interests: [],
      accept: false,
    },
  });

  const verifyForm = useForm<VerifyCodeSchemaType>({
    resolver: zodResolver(getVerifyCodeSchema()),
    defaultValues: {
      code: '',
    },
  });

  /**
   * Handles the submission of the signup form.
   * Shows the recaptcha and sets checkEmail state.
   *
   * @param {SignupSchemaType} e - The form values
   * @returns {Promise<void>}
   */
  const onSubmit = async (e: SignupSchemaType): Promise<void> => {
    setCheckEmail(true);
    setShowRecaptcha(true);
  };

  /**
   * Handles the submission of the email verification form.
   * Navigates to the company profile page.
   *
   * @param {VerifyCodeSchemaType} e - The verification form values
   * @returns {Promise<void>}
   */
  const handleCheckEmail = async (e: VerifyCodeSchemaType): Promise<void> => {
    // if (!result) return;
    router.push('/company-profile');
  };

  /**
   * Handles the verified submit with recaptcha token.
   * (Currently commented out)
   *
   * @param {string} token - The recaptcha token
   * @returns {Promise<void>}
   */
  const handleVerifiedSubmit = async (token: string): Promise<void> => {
    // try {
    //   const values = form.getValues();
    //   setIsProcessing(true);
    //   setError(null);
    //   setShowRecaptcha(false);
    //   const response = await apiFetch('/api/auth/signup', {
    //     method: 'POST',
    //     headers: {
    //       'Content-Type': 'application/json',
    //       'x-recaptcha-token': token,
    //     },
    //     body: JSON.stringify(values),
    //   });
    //   if (!response.ok) {
    //     const { message } = await response.json();
    //     setError(message);
    //   } else {
    //     router.push('/');
    //   }
    // } catch (err) {
    //   setError(
    //     err instanceof Error
    //       ? err.message
    //       : 'An unexpected error occurred. Please try again.',
    //   );
    // } finally {
    //   setIsProcessing(false);
    // }
  };

  if (success) {
    return (
      <Alert onClose={() => setSuccess(false)} id="signup-success-alert" data-testid="signup-success-alert">
        <AlertIcon>
          <Check />
        </AlertIcon>
        <AlertTitle>
          You have successfully signed up! Please check your email to verify your account and then{' '}
          <Link
            href="/signin/"
            className="text-primary hover:text-primary-darker"
            id="signup-success-login-link"
            data-testid="signup-success-login-link"
          >
            Log in
          </Link>
          .
        </AlertTitle>
      </Alert>
    );
  }

  return (
    <div className="w-[100vw] h-[100vh] bg-[#fafafa] relative overflow-y-auto" id="signup-root" data-testid="signup-root">
      <div className="flex h-full">
        <div className="lg:block hidden flex-1 relative bg-[#0D978B] overflow-hidden pt-[60px] xl:pl-[68px] xl:pr-[146px] pl-[50px] pr-[50px]" id="signup-left-panel" data-testid="signup-left-panel">
          <img src="/images/white-logo.svg" alt="logo" className="h-[40px]" id="signup-left-logo" data-testid="signup-left-logo" />
          <p className="text-[28px]/[36px] text-[#fff] mt-[97px] font-semibold md:break-words break-all" id="signup-left-description" data-testid="signup-left-description">
            <span className="opacity-[72%]">Join businesses using Projitt to streamline</span> HR
            operations, automate workflows, and empower growth
          </p>
          <div className="absolute xl:top-[387px] top-[550px] w-[1015px] right-[130px] rounded-[16px] bg-[#FFFFFF33] overflow-hidden p-[16px]" id="signup-left-image-container" data-testid="signup-left-image-container">
            <img src="/images/signup/dashboard.png" alt="logo" className="rounded-[12px] " id="signup-left-dashboard-image" data-testid="signup-left-dashboard-image" />
          </div>
        </div>
        {!checkEmail ? (
          <div className="lg:w-[50%] w-full lg:min-w-[737px] md:px-[136px] px-[30px] lg:pt-[130px] pt-[40px] pb-[80px] bg-white" id="signup-form-panel" data-testid="signup-form-panel">
            <div className="w-full flex justify-center mb-[20px] lg:hidden">
              <img src="/images/logo.png" alt="logo" className="h-[48px]" id="signup-mobile-logo" data-testid="signup-mobile-logo" />
            </div>
            <Suspense>
              <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="block w-full space-y-5" id="signup-form" data-testid="signup-form">
                  <div className="space-y-1.5 pb-3">
                    <h1 className="text-2xl font-semibold tracking-tight" id="signup-title" data-testid="signup-title">Create Account</h1>
                  </div>
                  <FormField
                    control={form.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-email-label" data-testid="signup-email-label">
                          Work Email Address
                        </FormLabel>
                        <FormControl>
                          <Input
                            placeholder="Enter work email addresss"
                            {...field}
                            className="h-[48px]"
                            id="signup-email-input"
                            data-testid="signup-email-input"
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <div className="flex flex-col sm:flex-row gap-[16px] w-full">
                    <div className="w-full">
                      <FormField
                        control={form.control}
                        name="companyName"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-company-label" data-testid="signup-company-label">
                              Company Name
                            </FormLabel>
                            <FormControl>
                              <Input
                                placeholder="Enter company name"
                                type="text"
                                className="h-[48px]"
                                {...field}
                                id="signup-company-input"
                                data-testid="signup-company-input"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                    <div className="w-full">
                      <FormField
                        control={form.control}
                        name="companySize"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-company-size-label" data-testid="signup-company-size-label">
                              What is your company size?
                            </FormLabel>
                            <FormControl>
                              <Select
                                value={field.value}
                                onValueChange={field.onChange}
                                indicatorVisibility={false}
                                data-testid="signup-company-size-select"
                              >
                                <SelectTrigger className="h-[48px]" id="signup-company-size-select" data-testid="signup-company-size-trigger">
                                  <SelectValue placeholder="Select a size" id="signup-company-size-value" data-testid="signup-company-size-value" />
                                </SelectTrigger>
                                <SelectContent id="signup-company-size-content" data-testid="signup-company-size-content">
                                  <SelectItem value="small" id="signup-company-size-small" data-testid="signup-company-size-small">1-10</SelectItem>
                                  <SelectItem value="medium" id="signup-company-size-medium" data-testid="signup-company-size-medium">11-100</SelectItem>
                                  <SelectItem value="large" id="signup-company-size-large" data-testid="signup-company-size-large">101-1000</SelectItem>
                                </SelectContent>
                              </Select>
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>
                  <div className="flex flex-col sm:flex-row gap-[16px] w-full">
                    <div className="w-full">
                      <FormField
                        control={form.control}
                        name="firstName"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-firstname-label" data-testid="signup-firstname-label">
                              First Name
                            </FormLabel>
                            <FormControl>
                              <Input
                                placeholder="Enter your first name"
                                type="text"
                                className="h-[48px]"
                                {...field}
                                id="signup-firstname-input"
                                data-testid="signup-firstname-input"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                    <div className="w-full">
                      <FormField
                        control={form.control}
                        name="lastName"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-lastname-label" data-testid="signup-lastname-label">
                              Last Name
                            </FormLabel>
                            <FormControl>
                              <Input
                                placeholder="Enter your last name"
                                type="text"
                                className="h-[48px]"
                                {...field}
                                id="signup-lastname-input"
                                data-testid="signup-lastname-input"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>
                  <FormField
                    control={form.control}
                    name="country"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-country-label" data-testid="signup-country-label">Country</FormLabel>
                        <FormControl>
                          <Select
                            value={field.value}
                            onValueChange={field.onChange}
                            indicatorVisibility={false}
                            data-testid="signup-country-select"
                          >
                            <SelectTrigger className="h-[48px]" id="signup-country-select" data-testid="signup-country-trigger">
                              <SelectValue placeholder="Select country" id="signup-country-value" data-testid="signup-country-value" />
                            </SelectTrigger>
                            <SelectContent id="signup-country-content" data-testid="signup-country-content">
                              <SelectItem value="us" id="signup-country-us" data-testid="signup-country-us">United States</SelectItem>
                              <SelectItem value="uk" id="signup-country-uk" data-testid="signup-country-uk">United Kingdom</SelectItem>
                            </SelectContent>
                          </Select>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <p className="text-[14px]/[22px] text-[#353535] mb-[10px]" id="signup-phone-label" data-testid="signup-phone-label">Phone Number</p>
                  <div className="flex flex-col sm:flex-row gap-[16px] w-full">
                    <div className="w-[300px]">
                      <Select
                        defaultValue="US"
                        indicatorVisibility={false}
                        onValueChange={(value) => {
                          setCode(phoneNumber.find((item) => item.key === value)?.code || '');
                        }}
                        data-testid="signup-phone-country-select"
                      >
                        <SelectTrigger className="h-[48px]" id="signup-phone-country-select" data-testid="signup-phone-country-trigger">
                          <SelectValue placeholder="Select a country" id="signup-phone-country-value" data-testid="signup-phone-country-value" />
                        </SelectTrigger>
                        <SelectContent id="signup-phone-country-content" data-testid="signup-phone-country-content">
                          {phoneNumber.map((item) => (
                            <SelectItem key={item.key} value={item.key} id={`signup-phone-country-${item.key}`} data-testid={`signup-phone-country-${item.key}`}>
                              {item.value}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="flex-1">
                      <FormField
                        control={form.control}
                        name="phoneNumber"
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Input
                                placeholder={`+${code}(555)000-0000`}
                                type="text"
                                className="h-[48px]"
                                {...field}
                                id="signup-phone-input"
                                data-testid="signup-phone-input"
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                    </div>
                  </div>
                  <FormField
                    control={form.control}
                    name="industry"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel id="signup-industry-label" data-testid="signup-industry-label">Select Industry</FormLabel>
                        <FormControl>
                          <Select
                            value={field.value}
                            onValueChange={field.onChange}
                            indicatorVisibility={false}
                            data-testid="signup-industry-select"
                          >
                            <SelectTrigger className="h-[48px]" id="signup-industry-select" data-testid="signup-industry-trigger">
                              <SelectValue placeholder="Select industry" id="signup-industry-value" data-testid="signup-industry-value" />
                            </SelectTrigger>
                            <SelectContent id="signup-industry-content" data-testid="signup-industry-content">
                              <SelectItem value="manufacturing" id="signup-industry-manufacturing" data-testid="signup-industry-manufacturing">Manufacturing</SelectItem>
                              <SelectItem value="retail" id="signup-industry-retail" data-testid="signup-industry-retail">Retail</SelectItem>
                              <SelectItem value="healthcare" id="signup-industry-healthcare" data-testid="signup-industry-healthcare">Healthcare</SelectItem>
                              <SelectItem value="technology" id="signup-industry-technology" data-testid="signup-industry-technology">Technology</SelectItem>
                              <SelectItem value="finance" id="signup-industry-finance" data-testid="signup-industry-finance">Finance</SelectItem>
                              <SelectItem value="education" id="signup-industry-education" data-testid="signup-industry-education">Education</SelectItem>
                              <SelectItem value="governemnt" id="signup-industry-government" data-testid="signup-industry-government">Government</SelectItem>
                              <SelectItem value="other" id="signup-industry-other" data-testid="signup-industry-other">Other</SelectItem>
                            </SelectContent>
                          </Select>
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name="interests"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-[14px]/[22px] text-[#353535]" id="signup-interests-label" data-testid="signup-interests-label">
                          What Product(s) are you interested in?
                        </FormLabel>
                        <div className="flex flex-col gap-[18px]" id="signup-interests-options" data-testid="signup-interests-options">
                          {[
                            { label: 'HR Management', value: 'hr_management' },
                            {
                              label: 'Vendor & Contract Management',
                              value: 'vendor_contract_management',
                            },
                            {
                              label: 'Asset & Facility Management',
                              value: 'asset_facility_management',
                            },
                            { label: 'Finance & Operation', value: 'finance_operation' },
                          ].map((option) => (
                            <label key={option.value} className="flex items-center gap-2" id={`signup-interests-label-${option.value}`} data-testid={`signup-interests-label-${option.value}`}>
                              <Checkbox
                                checked={
                                  Array.isArray(field.value)
                                    ? field.value.includes(option.value)
                                    : false
                                }
                                onCheckedChange={(checked) => {
                                  let newValue = Array.isArray(field.value) ? [...field.value] : [];
                                  if (checked) {
                                    if (!newValue.includes(option.value)) {
                                      newValue.push(option.value);
                                    }
                                  } else {
                                    newValue = newValue.filter((v) => v !== option.value);
                                  }
                                  field.onChange(newValue);
                                }}
                                id={`signup-interests-checkbox-${option.value}`}
                                data-testid={`signup-interests-checkbox-${option.value}`}
                              />
                              <span className="text-sm" id={`signup-interests-span-${option.value}`} data-testid={`signup-interests-span-${option.value}`}>{option.label}</span>
                            </label>
                          ))}
                        </div>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <div className="flex items-center space-x-2 pt-[40px]">
                    <FormField
                      control={form.control}
                      name="accept"
                      render={({ field }) => (
                        <FormItem>
                          <FormControl>
                            <div className="flex  gap-2.5">
                              <Checkbox
                                id="signup-accept-checkbox"
                                data-testid="signup-accept-checkbox"
                                checked={field.value}
                                onCheckedChange={(checked) => field.onChange(!!checked)}
                              />
                              <label
                                htmlFor="accept"
                                className="text-[14px]/[20px] text-[#626262]"
                                id="signup-accept-label"
                                data-testid="signup-accept-label"
                              >
                                By creating an account, you have read and agree to the Terms of Service and our Privacy Policy
                              </label>
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                  </div>
                  <div className="flex flex-col gap-2.5">
                    <Button
                      type="submit"
                      disabled={isProcessing}
                      className="h-[48px]"
                      id="signup-continue-btn"
                      data-testid="signup-continue-btn"
                    >
                      {isProcessing ? <LoaderCircleIcon className="size-4 animate-spin" /> : null}
                      Continue
                    </Button>
                  </div>
                </form>
              </Form>
            </Suspense>
          </div>
        ) : (
          <div className="lg:w-[50%] w-full lg:min-w-[737px] md:px-[136px] px-[30px] lg:pt-[130px] pt-[40px] pb-[80px] bg-white flex flex-col justify-center items-center" id="signup-verify-panel" data-testid="signup-verify-panel">
            <div className="w-full flex justify-center mb-[20px] lg:hidden">
              <img src="/images/logo.png" alt="logo" className="h-[48px]" id="signup-verify-mobile-logo" data-testid="signup-verify-mobile-logo" />
            </div>
            <div className="sm:w-[560px] w-full sm:px-[48px] px-[20px] h-full flex flex-col justify-center items-center" id="signup-verify-form-container" data-testid="signup-verify-form-container">
              <Form {...verifyForm}>
                <form
                  onSubmit={verifyForm.handleSubmit(handleCheckEmail)}
                  className="block w-full space-y-5"
                  id="signup-verify-form"
                  data-testid="signup-verify-form"
                >
                  <div className="space-y-1 pb-3">
                    <h1 className="text-[22px]/[30px] font-semibold tracking-tight" id="signup-verify-title" data-testid="signup-verify-title">
                      Check Your Email
                    </h1>
                    <p className="text-[14px]/[20px] text-[#787878]" id="signup-verify-desc" data-testid="signup-verify-desc">
                      Almost there! Enter the 6-digit code sent to your email to confirm
                    </p>
                  </div>
                  <div>
                    <FormField
                      control={verifyForm.control}
                      name="code"
                      render={({ field }) => (
                        <FormItem>
                          <FormControl>
                            <div className="flex justify-between">
                              <PinField
                                {...field}
                                length={6}
                                onComplete={(code) => console.log('Entered Code:', code)}
                                className="border border-[#bcbcbc] rounded-[10px] sm:w-[56px] w-full h-[56px] text-center text-xl mx-1 focus:outline-none focus:ring-[3px] focus:ring-[#0D978B33]"
                                id="signup-verify-pinfield"
                                data-testid="signup-verify-pinfield"
                              />
                            </div>
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    <p className="text-[14px]/[20px] text-[#787878] mt-[14px]" id="signup-verify-resend-label" data-testid="signup-verify-resend-label">
                      Didn&apos;t receive your code?{' '}
                      <span className="text-[#0D978B] font-medium" id="signup-verify-resend" data-testid="signup-verify-resend">Resend Code</span>
                    </p>
                  </div>
                  <Button
                    type="submit"
                    disabled={!!success || isProcessing}
                    className="w-full h-[48px] text-[14px]/[20px] font-semibold"
                    id="signup-verify-btn"
                    data-testid="signup-verify-btn"
                  >
                    {isProcessing ? <LoaderCircleIcon className="animate-spin" /> : null}
                    Verify & Continue
                  </Button>
                  <div className="flex justify-center">
                    <span
                      className="text-[14px]/[20px] text-[#0D978B] hover:text-[#086159] text-center cursor-pointer"
                      onClick={() => {
                        setCheckEmail(false);
                      }}
                      id="signup-verify-change-email"
                      data-testid="signup-verify-change-email"
                    >
                      Change email
                    </span>
                  </div>
                </form>
              </Form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
