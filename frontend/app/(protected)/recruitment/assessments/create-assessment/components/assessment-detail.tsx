import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea";

/**
 * Component: AssessmentDetail
 * ----------------------------
 * Renders the form for entering assessment details such as:
 * - Name
 * - Description
 * - Assessment Type (Psychometric or Coding)
 * - Estimated Duration
 * 
 * This component is used to collect and validate inputs before proceeding with assessment creation.
 */
interface AssessmentDetailProps {
    assessmentData: any;
    setAssessmentData: any;
    errors?: {
        name?: string;
        description?: string;
        type?: string;
        duration?: string;
    };
    triggerValidation?: boolean;
}

/**
 * Function: AssessmentDetail
 * --------------------------
 * Displays the assessment input form fields and handles data change events. 
 * Also displays validation errors if `triggerValidation` is enabled.
 */
export default function AssessmentDetail({
    assessmentData,
    setAssessmentData,
    errors = {},
    triggerValidation = false,
}: AssessmentDetailProps) {
    return (
        <div className="sm:w-[586px] w-full">
            <div>
                <Label htmlFor="assessment-name">Assessment Name</Label>
                <Input
                    id="assessment-name"
                    data-test-id="input-assessment-name"
                    type="text"
                    placeholder="e.g Sales Reprensentative"
                    className="sm:w-[360px] w-full h-[48px] mt-[12px]"
                    value={assessmentData?.name}
                    onChange={(e) => setAssessmentData({ ...assessmentData, name: e.target.value })}
                />
                {triggerValidation && errors.name && (
                    <span className="text-red-500 text-xs ">{errors.name}</span>
                )}
            </div>

            <div className="mt-[36px] flex flex-col">
                <Label htmlFor="assessment-description">Description</Label>
                <Textarea
                    id="assessment-description"
                    data-test-id="input-assessment-description"
                    placeholder="e.g Sales Reprensentative"
                    className="sm:w-[360px] w-full h-[61px] mt-[12px]"
                    value={assessmentData?.description}
                    onChange={(e) => setAssessmentData({ ...assessmentData, description: e.target.value })}
                />
                {triggerValidation && errors.description && (
                    <span className="text-red-500 text-xs ">{errors.description}</span>
                )}
            </div>

            <div className="mt-[36px]">
                <Label>Assessment Type</Label>
                <div className="flex gap-[20px] sm:flex-row flex-col mt-[12px]">
                    <div
                        id="type-psychometric"
                        data-test-id="card-psychometric"
                        className={`w-full border rounded-[16px] p-[24px] cursor-pointer bg-white ${
                            assessmentData.type === 'psychometric' ? 'border-[#0d978b]' : 'border-[#e9e9e9]'
                        }`}
                        onClick={() => setAssessmentData({ ...assessmentData, type: 'psychometric' })}
                    >
                        <div
                            className={`w-[24.5px] h-[24.5px] border-[1.17px] rounded-full flex items-center justify-center ${
                                assessmentData.type === 'psychometric' ? 'border-[#0d978b] bg-[#0d978b]' : 'border-[#787878]'
                            }`}
                        >
                            <div className="w-[10.5px] h-[10.5px] bg-white rounded-full"></div>
                        </div>
                        <p
                            className={`text-[16px]/[24px] font-medium mt-[12px] ${
                                assessmentData.type === 'psychometric' ? 'text-[#0d978b]' : 'text-[#4b4b4b]'
                            }`}
                        >
                            Psychometric Assessment
                        </p>
                        <p className="text-[13px]/[20px] text-[#8f8f8f] mt-[4px]">
                            Import video from an external link e.g Youtube, Udemy etc.
                        </p>
                    </div>

                    <div
                        id="type-coding"
                        data-test-id="card-coding"
                        className={`w-full border rounded-[16px] p-[24px] cursor-pointer bg-white ${
                            assessmentData.type === 'coding' ? 'border-[#0d978b]' : 'border-[#e9e9e9]'
                        }`}
                        onClick={() => setAssessmentData({ ...assessmentData, type: 'coding' })}
                    >
                        <div
                            className={`w-[24.5px] h-[24.5px] border-[1.17px] rounded-full flex items-center justify-center ${
                                assessmentData.type === 'coding' ? 'border-[#0d978b] bg-[#0d978b]' : 'border-[#787878]'
                            }`}
                        >
                            <div className="w-[10.5px] h-[10.5px] bg-white rounded-full"></div>
                        </div>
                        <p
                            className={`text-[16px]/[24px] font-medium mt-[12px] ${
                                assessmentData.type === 'coding' ? 'text-[#0d978b]' : 'text-[#4b4b4b]'
                            }`}
                        >
                            Coding Asessment
                        </p>
                        <p className="text-[13px]/[20px] text-[#8f8f8f] mt-[4px]">
                            Import video from an external link e.g Youtube, Udemy etc.
                        </p>
                    </div>
                </div>
            </div>

            <div className="mt-[36px]">
                <Label htmlFor="assessment-duration">Estimated Duration</Label>
                <div className="relative sm:w-[360px] w-full">
                    <Input
                        id="assessment-duration"
                        data-test-id="input-assessment-duration"
                        type="text"
                        className="w-full h-[48px] mt-[12px]"
                        value={assessmentData?.duration}
                        onChange={(e) => setAssessmentData({ ...assessmentData, duration: e.target.value })}
                    />
                    <span className="absolute text-[12px]/[18px] right-[16px] top-1/2 transform -translate-y-1/2 text-[#a5a5a5]">
                        min
                    </span>
                </div>
                {triggerValidation && errors.duration && (
                    <span className="text-red-500 text-xs ">{errors.duration}</span>
                )}
            </div>
        </div>
    );
}
