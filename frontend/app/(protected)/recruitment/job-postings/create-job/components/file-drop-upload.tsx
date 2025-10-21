import { Label } from "@/components/ui/label";
import { useCallback } from "react";
import { useDropzone } from "react-dropzone";
import { File, FileImage, FileText, FileUp } from "lucide-react";

/**
 * FileDropUpload is a file upload component that allows users to drag and drop
 * or click to select a file for upload. It shows file type icons based on MIME type,
 * and updates the selected file state in the parent component.
 */
export default function FileDropUpload({
    label,
    setFile,
    file,
}: {
    label: string;
    setFile: React.Dispatch<React.SetStateAction<File | null>>;
    file: File | null;
}) {
    /**
     * Callback triggered when a file is dropped or selected.
     * It updates the parent component's file state.
     */
    const onDrop = useCallback((acceptedFiles: File[]) => {
        setFile(acceptedFiles[0])
    }, [])

    const { getRootProps, getInputProps, isDragActive } = useDropzone({ onDrop })

    return (
        <div>
            <div className="mt-[19px]">
                <Label
                    className="text-[14px]/[22px] font-medium text-[#a5a5a5]"
                    data-test-id="file-upload-label"
                >
                    {label}
                </Label>
                <div
                    {...getRootProps()}
                    className="mt-[6px] sm:w-[464px] h-[150px] w-full border-1 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer hover:border-teal-400 transition"
                    data-test-id="file-drop-zone"
                >
                    <input
                        {...getInputProps()}
                        data-test-id="file-input"
                    />
                    <div
                        className="flex flex-col items-center justify-center text-gray-400"
                        data-test-id="file-drop-content"
                    >
                        {file && (
                            <div
                                className="flex flex-col items-center justify-center"
                                data-test-id="file-selected-preview"
                            >
                                {file.type === 'application/pdf' && <FileText className="size-[56px] text-[#0D978B]" />}
                                {file.type === 'application/msword' && <FileText className="size-[56px] text-[#0D978B]" />}
                                {file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' && <FileText className="size-[56px] text-[#0D978B]" />}
                                {file.type !== 'application/pdf' && file.type !== 'application/msword' && file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' && <File className="size-[56px] text-[#0D978B]" />}
                                <p className="text-[14px]/[20px]">{file.name}</p>
                            </div>
                        )}
                        {!file && (
                            <>
                                <FileUp className="size-[56px] text-[#0D978B]" />
                                <p
                                    className="text-[14px]/[20px] mt-[10px]"
                                    data-test-id="file-drop-instruction"
                                >
                                    {isDragActive ? (
                                        <span className="text-[#0D978B] font-medium">Drop file here...</span>
                                    ) : (
                                        <>
                                            Drop file or{' '}
                                            <span className="text-[#0D978B] font-medium">click to upload</span>
                                        </>
                                    )}
                                </p>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    )
}
