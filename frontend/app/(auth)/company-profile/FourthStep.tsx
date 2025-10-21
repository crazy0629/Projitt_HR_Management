'use client';

import { useState } from 'react';
import { ManageSchemaType } from '../forms/manage-schema';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { JSX } from 'react';
interface FourthStepProps {
  step: string;
  setStep: React.Dispatch<React.SetStateAction<string>>;
}

/**
 * FourthStep Component
 * 
 * This component renders the fourth step of a multi-step company profile setup form.
 * It allows users to select roles within their organization that need access to Projitt.
 * Users can select from predefined roles and specify others if needed.
 * Navigation buttons allow moving to the previous or next step.
 * 
 * @param {FourthStepProps} props - Contains current step and setter function to update step
 * @returns {JSX.Element} The rendered fourth step UI
 */
export default function FourthStep({ step, setStep }: FourthStepProps): JSX.Element {
  // Local state to track selected roles under key 'value' as array of strings
  const [childSelections, setChildSelections] = useState<{ [key: string]: string[] }>({ value: [] });

  // Placeholder states, kept unused as per instruction to not remove unused parts
  const [multipleCountries, setMultipleCountries] = useState<boolean>(false);
  const [selected, setSelected] = useState<any[]>([]);

  /**
   * Handles form submission.
   * Currently logs the form values and proceeds to next step 'step5'.
   * 
   * @param {ManageSchemaType} values - The form values to submit
   * @returns {Promise<void>}
   */
  const onSubmit = async (values: ManageSchemaType): Promise<void> => {
    console.log(values);
    setStep('step5');
  };

  // Predefined organization roles options
  const organization = [
    { label: 'IT Manager', value: 'it_manager' },
    { label: 'Finance/Payroll Officer', value: 'finance_payroll_officer' },
    { label: 'Hiring Manager', value: 'hiring_manager' },
    { label: 'Talent Partners', value: 'talent_partners' },
    { label: 'Company Admin / Founder', value: 'company_admin_founder' },
    { label: 'Others (specify)', value: 'others' },
  ];

  return (
    <div
      className="sm:w-[1000px] w-[100%] border border-[#e9e9e9] rounded-[16px] bg-white py-[60px] px-[48px]"
      id="fourth-step-container"
      data-testid="fourth-step-container"
    >
      <div className="flex sm:flex-row flex-col items-center justify-between">
        <p
          className="text-[22px]/[30px] font-semibold tracking-tight text-[#353535] sm:text-left text-center"
          id="fourth-step-title"
          data-testid="fourth-step-title"
        >
          Complete your company profile
        </p>
        <p
          className="text-[16px]/[26px] text-[#353535] sm:mt-0 mt-[10px]"
          id="fourth-step-progress"
          data-testid="fourth-step-progress"
        >
          Step 1/4
        </p>
      </div>

      <div className="w-full h-[3px] bg-[#e9e9e9] md:mt-[32px] mt-[10px]" id="progress-bar">
        <div className="w-[20%] h-[3px] bg-[#0d978b]" id="progress-bar-fill" data-testid="progress-bar-fill"></div>
      </div>

      <div className="sm:w-[486px] w-[100%] mx-auto mt-[44px]">
        <p
          className="text-[18px]/[30px] text-[#353535]"
          id="organization-access-question"
          data-testid="organization-access-question"
        >
          Who else in your organization will need access to Projitt?
        </p>

        <div className="mt-8 flex flex-col gap-[16px] w-[254px] mx-auto" id="organization-options" data-testid="organization-options">
          {organization.map((option) => (
            <label
              key={option.value}
              className="flex items-center gap-2"
              htmlFor={`checkbox-${option.value}`}
              id={`label-${option.value}`}
              data-testid={`label-${option.value}`}
            >
              <Checkbox
                id={`checkbox-${option.value}`}
                data-testid={`checkbox-${option.value}`}
                checked={
                  Array.isArray(childSelections.value)
                    ? childSelections.value.includes(option.value)
                    : false
                }
                onCheckedChange={(checked) => {
                  let newValue = Array.isArray(childSelections.value)
                    ? [...childSelections.value]
                    : [];
                  if (checked) {
                    if (!newValue.includes(option.value)) {
                      newValue.push(option.value);
                    }
                  } else {
                    newValue = newValue.filter((v) => v !== option.value);
                  }
                  setChildSelections({
                    ...childSelections,
                    value: newValue,
                  });
                }}
              />
              <span className="text-sm" id={`label-text-${option.value}`} data-testid={`label-text-${option.value}`}>
                {option.label}
              </span>
            </label>
          ))}
          <Input
            id="others-specify-input"
            data-testid="others-specify-input"
            className={`h-[48px] ${childSelections?.value?.includes('others') ? 'block' : 'invisible'}`}
            placeholder="Please specify"
          />
        </div>

        <div className="px-[10px] flex gap-[10px] mt-[70px] justify-center" id="navigation-buttons" data-testid="navigation-buttons">
          <Button
            type="button"
            onClick={() => setStep('step2')}
            className="w-[151px] bg-white text-[#053834] border-[#E9E9E9] border-[1px] h-[48px] hover:border-[#053834] hover:bg-white"
            id="back-button"
            data-testid="back-button"
          >
            Back
          </Button>
          <Button
            onClick={() => setStep('dateTimePick')}
            className="w-[151px] h-[48px]"
            id="continue-button"
            data-testid="continue-button"
          >
            Continue
          </Button>
        </div>
      </div>
    </div>
  );
}
