'use client';

import { JSX, useEffect, useState } from 'react';
import FirstStep from './FirstStep';
import SecondStep from './SecondStep';
import ThirdStep from './ThirdStep';
import FourthStep from './FourthStep';
import DateTimePick from './DateTimePick';

/**
 * Page Component
 * 
 * This component manages the multi-step company profile setup flow.
 * It tracks the current step and renders the corresponding step component.
 * The footer includes Terms of Service and Privacy Policy links with proper unique locators for UI test automation.
 * 
 * @returns {JSX.Element} The rendered page with step components and footer
 */
export default function Page(): JSX.Element {
  const [step, setStep] = useState<string>('step1');

  useEffect(() => {
    console.log(step);
  }, [step]);

  return (
    <div
      className="w-full h-full flex flex-col justify-between overflow-y-auto"
      id="page-container"
      data-testid="page-container"
    >
      <div className="flex flex-col flex-1">
        <div
          className="h-[95px] border-b border-[#e9e9e9] w-full flex items-center justify-center"
          id="header"
          data-testid="header"
        >
          <img
            src="/images/logo.png"
            alt="logo"
            className="h-[40px]"
            id="logo-image"
            data-testid="logo-image"
          />
        </div>

        <div
          className="mt-[56px] flex justify-center flex-1 overflow-hidden px-[10px]"
          id="step-container"
          data-testid="step-container"
        >
          {step === 'step1' && <FirstStep step={step} setStep={setStep} />}
          {step === 'step2' && <SecondStep step={step} setStep={setStep} />}
          {step === 'step3' && <ThirdStep step={step} setStep={setStep} />}
          {step === 'step4' && <FourthStep step={step} setStep={setStep} />}
          {step === 'dateTimePick' && <DateTimePick step={step} setStep={setStep} />}
        </div>
      </div>

      <div
        className="px-[48px] pb-[50px] flex sm:flex-row flex-col items-center gap-[10px] justify-between pt-[20px]"
        id="footer"
        data-testid="footer"
      >
        <div className="flex gap-[16px]" id="footer-links" data-testid="footer-links">
          <span
            className="text-[14px]/[22px] underline text-[#a19e9e]"
            id="terms-of-service"
            data-testid="terms-of-service"
          >
            Terms of Service
          </span>
          <div
            className="w-[1px] h-[20px] bg-[#a19e9e]"
            aria-hidden="true"
            id="footer-divider"
            data-testid="footer-divider"
          ></div>
          <span
            className="text-[14px]/[22px] underline text-[#a19e9e]"
            id="privacy-policy"
            data-testid="privacy-policy"
          >
            Privacy Policy
          </span>
        </div>
        <span
          className="text-[14px]/[22px] text-[#a19e9e]"
          id="copyright"
          data-testid="copyright"
        >
          © 2025 Projitt
        </span>
      </div>
    </div>
  );
}
