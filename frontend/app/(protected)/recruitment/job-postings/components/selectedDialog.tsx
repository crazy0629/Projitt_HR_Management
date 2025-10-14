import { Ban, Trash, Upload } from "lucide-react";
import { JSX } from "react";

/**
 * SelectedDialog component displays actions and selection info when rows are selected.
 * Shows number of selected items, total count, and buttons for Select All, Close, Delete, and Export CSV.
 * 
 * @param {Object} props
 * @param {string[]} props.selectedRows - Array of selected row IDs.
 * @param {number} props.totalCount - Total number of rows available.
 * @returns JSX.Element - The UI for the selection dialog with action buttons.
 */
export const SelectedDialog = ({
    selectedRows,
    totalCount,
    setSelectedRows,
    allData,
    setRowSelection,
}: {
    selectedRows: string[];
    totalCount: number;
    setSelectedRows: (rows: string[]) => void;
    allData: any[];
    setRowSelection: (selection: any) => void;
}): JSX.Element => {
    return (
        <div
            className="w-full flex justify-center items-center absolute sm:bottom-[45px] bottom-[160px] z-[1000] px-[40px]"
            id="selected-dialog-container"
            data-testid="selected-dialog-container"
        >
            <div
                className="bg-[#053834] px-[20px] py-[12px] sm:w-[670px] w-full rounded-[12px] flex sm:flex-row flex-col sm:justify-between items-center"
                id="selected-dialog-content"
                data-testid="selected-dialog-content"
            >
                <div className="flex items-center" id="selection-info" data-testid="selection-info">
                    <div
                        className="pr-[16px] border-r text-[15px]/[20px] border-[#626262] text-[#d2d2d2] py-[4px]"
                        id="selection-count"
                        data-testid="selection-count"
                    >
                        {selectedRows.length} of <span className="text-[#fff]" id="total-count" data-testid="total-count">{totalCount}</span> selected
                    </div>
                    <button
                        className="text-[15px]/[20px] pl-[16px] text-white cursor-pointer"
                        id="select-all-button"
                        data-testid="select-all-button"
                        type="button"
                        onClick={() => {
                            // Create a row selection object where all rows are selected
                            const allSelected = allData.reduce((acc, item) => {
                                acc[item.id] = true;
                                return acc;
                            }, {} as Record<string, boolean>);

                            // Update both the table's row selection and the selected rows
                            setRowSelection(allSelected);
                            setSelectedRows(allData.map((item: any) => item.id));
                        }}
                    >
                        Select all
                    </button>
                </div>
                <div className="flex items-center" id="action-buttons" data-testid="action-buttons">
                    <button
                        className="text-[15px]/[20px] pl-[16px] text-white py-[4px] border-r border-[#626262] px-[16px] flex items-center gap-[6px]"
                        id="close-button"
                        data-testid="close-button"
                        type="button"
                    >
                        <Ban className="size-[16px]" />
                        Close
                    </button>
                    <button
                        className="text-[15px]/[20px] pl-[16px] text-white py-[4px] border-r border-[#626262] px-[16px] flex items-center gap-[6px]"
                        id="delete-button"
                        data-testid="delete-button"
                        type="button"
                    >
                        <Trash className="size-[16px]" />
                        Delete
                    </button>
                    <button
                        className="text-[15px]/[20px] pl-[16px] text-white py-[4px] px-[16px] flex items-center gap-[6px]"
                        id="export-csv-button"
                        data-testid="export-csv-button"
                        type="button"
                    >
                        <Upload className="size-[16px]" />
                        Export CSV
                    </button>
                </div>
            </div>
        </div>
    );
};
