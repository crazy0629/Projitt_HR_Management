/**
 * Stepper component displaying a vertical progress tracker.
 * Highlights current step, completed steps with check icon,
 * and upcoming steps in outlined style.
 */

import { Check } from "lucide-react"; // or use any checkmark SVG/icon

import { JSX, useState } from 'react';
const steps = [
  { title: "Job Details", subtitle: "Enter Job Details" },
  { title: "Job Description", subtitle: "Clarify responsibilities" },
  { title: "Applicant Questions", subtitle: "Screen for Fit" },
  { title: "Hiring Pipeline", subtitle: "Interview Stages" },
  { title: "Review & Publish", subtitle: "Final check" },
];

/**
 * Renders the stepper UI with vertical line indicators and labeled steps.
 * 
 * @param currentStep - The current active step (1-indexed)
 * @returns JSX.Element
 */
export default function Stepper({ currentStep = 2 }: { currentStep?: number }): JSX.Element {
  return (
    <div id="vertical-stepper" data-testid="vertical-stepper">
      <div className="flex flex-col gap-[56px] relative">
        {steps.map((step, index) => {
          const stepNumber = index + 1;
          const isCompleted = stepNumber < currentStep;
          const isActive = stepNumber === currentStep;

          return (
            <div
              key={index}
              className="flex items-start gap-[12px] relative"
              id={`step-${stepNumber}`}
              data-testid={`step-${stepNumber}`}
            >
              {/* Vertical line */}
              {index !== steps.length - 1 && (
                <div
                  className={`absolute top-[40px] left-[18.5px] h-[46px] border-l border-dashed ${index < currentStep ? "border-[#0D978B]" : "border-[#626262]"
                    } z-0`}
                  id={`step-line-${stepNumber}`}
                  data-testid={`step-line-${stepNumber}`}
                ></div>
              )}

              {/* Circle */}
              <div
                className="relative z-1"
                id={`step-circle-${stepNumber}`}
                data-testid={`step-circle-${stepNumber}`}
              >
                {isCompleted ? (
                  <div className="w-[36px] h-[36px] rounded-full bg-[#0D978B] text-white flex items-center justify-center">
                    <Check size={16} strokeWidth={3} />
                  </div>
                ) : isActive ? (
                  <div className="w-[36px] h-[36px] rounded-full bg-[#0D978B] text-white flex items-center justify-center text-[12px] font-medium">
                    {stepNumber}
                  </div>
                ) : (
                  <div className="w-[36px] h-[36px] rounded-full border-[1px] border-[#626262] text-[#626262] flex items-center justify-center text-[12px] font-medium">
                    {stepNumber}
                  </div>
                )}
              </div>

              {/* Labels */}
              <div
                className="step-labels"
                id={`step-labels-${stepNumber}`}
                data-testid={`step-labels-${stepNumber}`}
              >
                <div
                  className={`text-[14px]/[18px] font-medium ${isCompleted || isActive ? "text-teal-600" : "text-[#626262]"
                    }`}
                  id={`step-title-${stepNumber}`}
                  data-testid={`step-title-${stepNumber}`}
                >
                  {step.title}
                </div>
                <div
                  className="text-[11px]/[12px] text-[#626262] mt-[3px]"
                  id={`step-subtitle-${stepNumber}`}
                  data-testid={`step-subtitle-${stepNumber}`}
                >
                  {step.subtitle}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
