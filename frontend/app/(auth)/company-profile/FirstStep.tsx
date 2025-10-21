'use client';

/**
 * FirstStep Component
 * -------------------
 * Step 1 in a multi-step company profile form.
 * Captures logo, company size, industry, phone number, and website.
 */

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Camera } from 'lucide-react';
import { getCompanySchema, CompanySchemaType } from '../forms/company-schema';
import {
  Form, FormControl, FormField, FormItem, FormLabel, FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { JSX } from 'react';

interface FirstStepProps {
  step: string;
  setStep: React.Dispatch<React.SetStateAction<string>>;
}

export default function FirstStep({ step, setStep }: FirstStepProps): JSX.Element {
  const form = useForm<CompanySchemaType>({
    resolver: zodResolver(getCompanySchema()),
    defaultValues: {
      logo: '',
      companySize: 'small',
      industry: 'manufacturing',
      phoneNumber: '',
      websiteURL: '',
    },
  });

  const onSubmit = async (values: CompanySchemaType) => {
    console.log(values);
    setStep('step2');
  };

  return (
    <div
      className="sm:w-[1000px] w-full border border-[#e9e9e9] rounded-[16px] bg-white py-[60px] px-[48px]"
      data-testid="first-step-form"
    >
      {/* Header Section */}
      <div className="flex sm:flex-row flex-col items-center justify-between">
        <p className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535] sm:text-left text-center">
          Complete your company profile
        </p>
        <p className="text-[16px]/[26px] text-[#353535] sm:mt-0 mt-[10px]">Step 1/4</p>
      </div>

      {/* Progress Bar */}
      <div className="w-full h-[3px] bg-[#e9e9e9] md:mt-[32px] mt-[10px]">
        <div className="w-[20%] h-[3px] bg-[#0d978b]" />
      </div>

      {/* Form Section */}
      <div className="sm:w-[486px] w-full mx-auto mt-[44px]">
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">

            {/* Company Logo Upload */}
            <FormField
              control={form.control}
              name="logo"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[14px]/[22px] text-[#353535]">Company logo</FormLabel>
                  <FormControl>
                    <div>
                      <label
                        htmlFor="logo-upload"
                        className="flex flex-col items-center justify-center sm:w-[261px] w-full h-[84px] bg-[#d9efef] rounded-[12px] cursor-pointer transition hover:bg-[#c7e7e7] border-2 border-transparent hover:border-[#0d978b]"
                      >
                        {field.value && field.value.type === 'image/png' ? (
                          <img
                            src={URL.createObjectURL(field.value)}
                            alt="Logo Preview"
                            className="h-[60px] object-contain"
                          />
                        ) : (
                          <>
                            <Camera className="h-[15px] w-[15px] text-[#053834]" />
                            <span className="mt-2 text-[#053834] text-[14px]/[22px] font-medium">
                              Click to upload
                            </span>
                          </>
                        )}
                        <input
                          id="logo-upload"
                          type="file"
                          accept="image/*"
                          data-testid="logo-upload"
                          onChange={(e) => {
                            const file = e.target.files?.[0];
                            field.onChange(file);
                          }}
                          className="hidden"
                        />
                      </label>
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Company Size Dropdown */}
            <FormField
              control={form.control}
              name="companySize"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[14px]/[22px] text-[#353535]">
                    What is your company size?
                  </FormLabel>
                  <FormControl>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <SelectTrigger className="h-[48px]" data-testid="select-size">
                        <SelectValue placeholder="Select a size" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="small">1-10</SelectItem>
                        <SelectItem value="medium">11-50</SelectItem>
                        <SelectItem value="large">51-200</SelectItem>
                      </SelectContent>
                    </Select>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Industry Dropdown */}
            <FormField
              control={form.control}
              name="industry"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[14px]/[22px] text-[#353535]">Select Industry</FormLabel>
                  <FormControl>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <SelectTrigger className="h-[48px]" data-testid="select-industry">
                        <SelectValue placeholder="Select industry" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="manufacturing">Manufacturing</SelectItem>
                        <SelectItem value="retail">Retail</SelectItem>
                        <SelectItem value="healthcare">Healthcare</SelectItem>
                        <SelectItem value="technology">Technology</SelectItem>
                        <SelectItem value="finance">Finance</SelectItem>
                        <SelectItem value="education">Education</SelectItem>
                        <SelectItem value="government">Government</SelectItem>
                        <SelectItem value="other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Phone Number Field */}
            <FormField
              control={form.control}
              name="phoneNumber"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[14px]/[22px] text-[#353535]">Phone Number</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="Enter phone number"
                      className="h-[48px]"
                      data-testid="input-phone"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Website URL Field */}
            <FormField
              control={form.control}
              name="websiteURL"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-[14px]/[22px] text-[#353535]">Website URL</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="Enter website URL"
                      className="h-[48px]"
                      data-testid="input-url"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Continue Button */}
            <div className="w-full flex justify-center">
              <Button
                type="submit"
                className="w-[180px] h-[48px] bg-[#0d978b] text-white rounded-[8px]"
                data-testid="btn-continue"
              >
                Continue
              </Button>
            </div>
          </form>
        </Form>
      </div>
    </div>
  );
}
