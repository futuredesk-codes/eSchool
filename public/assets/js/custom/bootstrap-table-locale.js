/**
 * Bootstrap Table English translation
 * Author: Zhixin Wen<wenzhixin2010@gmail.com>
 */

$.fn.bootstrapTable.locales['en-US'] = $.fn.bootstrapTable.locales['en'] = {
    formatAddLevel() {
        return trans('Add Level')
    },

    formatAdvancedCloseButton() {
        return trans('Close')
    },

    formatAdvancedSearch() {
        return trans('Advanced search')
    },

    formatAllRows() {
        return trans('All')
    },

    formatAutoRefresh() {
        return trans('Auto Refresh')
    },

    formatCancel() {
        return trans('Cancel')
    },

    formatClearSearch() {
        return trans('Clear Search')
    },

    formatColumn() {
        return trans('Column')
    },

    formatColumns() {
        return trans('Columns')
    },

    formatColumnsToggleAll() {
        return trans('Toggle all')
    },

    formatCopyRows() {
        return trans('Copy Rows')
    },

    formatDeleteLevel() {
        return trans('Delete Level')
    },

    formatDetailPagination(totalRows) {
        return `Showing ${totalRows} rows`
    },

    formatDuplicateAlertDescription() {
        return trans('Please remove or change any duplicate column.')
    },

    formatDuplicateAlertTitle() {
        return trans('Duplicate(s) detected!')
    },

    formatExport() {
        return trans('Export data')
    },

    formatFilterControlSwitch() {
        return trans('Hide/Show controls')
    },

    formatFilterControlSwitchHide() {
        return trans('Hide controls')
    },

    formatFilterControlSwitchShow() {
        return trans('Show controls')
    },

    formatFullscreen() {
        return trans('Fullscreen')
    },

    formatJumpTo() {
        return trans('GO')
    },

    formatLoadingMessage() {
        return trans('Loading, please wait')
    },

    formatMultipleSort() {
        return trans('Multiple Sort')
    },

    formatNoMatches() {
        return trans('No matching records found')
    },

    formatOrder() {
        return trans('Order')
    },

    formatPaginationSwitch() {
        return trans('Hide/Show pagination')
    },

    formatPaginationSwitchDown() {
        return trans('Show pagination')
    },

    formatPaginationSwitchUp() {
        return trans('Hide pagination')
    },

    formatPrint() {
        return trans('Print')
    },

    formatRecordsPerPage(pageNumber) {
        return `${pageNumber} ${trans('rows per page')}`
    },

    formatRefresh() {
        return trans('Refresh')
    },

    formatSRPaginationNextText() {
        return trans('next page')
    },

    formatSRPaginationPageText(page) {
        return `${trans('to page')} ${page} `
    },

    formatSRPaginationPreText() {
        return trans('previous page')
    },

    formatSearch() {
        return trans('Search')
    },

    formatShowingRows(pageFrom, pageTo, totalRows, totalNotFiltered) {
        if (totalNotFiltered !== undefined && totalNotFiltered > 0 && totalNotFiltered > totalRows) {
            return `${trans('Showing')} ${pageFrom} ${trans('to')} ${pageTo} ${trans('of')} ${totalRows} ${trans('rows')} (filtered from ${totalNotFiltered} ${trans('total rows')})`
        }

        return `${trans('Showing')} ${pageFrom} ${trans('to')} ${pageTo} ${trans('of')} ${totalRows} ${trans('rows')} `
    },

    formatSort() {
        return trans('Sort')
    },

    formatSortBy() {
        return trans('Sort by')
    },

    formatSortOrders() {
        return {
            asc: trans('Ascending'),
            desc: trans('Descending')
        }
    },

    formatThenBy() {
        return trans('Then by')
    },

    formatToggleCustomViewOff() {
        return trans('Hide custom view')
    },

    formatToggleCustomViewOn() {
        return trans('Show custom view')
    },

    formatToggleOff() {
        return trans('Hide card view')
    },

    formatToggleOn() {
        return trans('Show card view')
    },

    formatExport() {
        return trans('Export data')
    },

    // Export file type localization functions
    formatExportType(type) {
        const exportTypes = {
            'json': trans('JSON'),
            'xml': trans('XML'),
            'png': trans('PNG'),
            'csv': trans('CSV'),
            'txt': trans('TXT'),
            'sql': trans('SQL'),
            'doc': trans('MS-Word'),
            'excel': trans('MS-Excel'),
            'xlsx': trans('MS-Excel (OpenXML)'),
            'powerpoint': trans('MS-Powerpoint'),
            'pdf': trans('PDF')
        };
        return exportTypes[type] || type.toUpperCase();
    },

    formatExportTypes() {
        return {
            'json': trans('JSON'),
            'xml': trans('XML'),
            'png': trans('PNG'),
            'csv': trans('CSV'),
            'txt': trans('TXT'),
            'sql': trans('SQL'),
            'doc': trans('MS-Word'),
            'excel': trans('MS-Excel'),
            'xlsx': trans('MS-Excel (OpenXML)'),
            'powerpoint': trans('MS-Powerpoint'),
            'pdf': trans('PDF')
        };
    }

}

Object.assign($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['en-US'])

// Override Bootstrap Table export functionality to use localized export type names
$(document).ready(function () {
    // Function to localize export type names
    function localizeExportTypes() {
        $('.export [data-type]').each(function () {
            const $this = $(this);
            const type = $this.data('type');
            const exportTypes = $.fn.bootstrapTable.locales['en-US'].formatExportTypes();
            const localizedName = exportTypes[type] || type.toUpperCase();
            $this.text(localizedName);
        });
    }

    // Override Bootstrap Table export type names after table initialization
    $(document).on('post-body.bs.table', function () {
        localizeExportTypes();
    });

    // Also localize on page load for existing tables
    localizeExportTypes();
});
