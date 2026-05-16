 <!DOCTYPE html>
 <html lang="en">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Academic Calendar {{ $report_year }}</title>
     <style>
         * {
             margin: 0;
             padding: 0;
             box-sizing: border-box;
         }

         body {
             font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
             color: #333;
             background: #ffffff;
             padding: 40px 30px;
         }

         /* Header */
         .header {
             text-align: center;
             margin-bottom: 50px;
             padding-bottom: 30px;
             border-bottom: 2px solid #e8e8e8;
         }

         .logo {
             width: 70px;
             height: 70px;
             object-fit: contain;
             margin-bottom: 15px;
         }

         .school-name {
             font-size: 32px;
             font-weight: 700;
             color: #1a1a1a;
             margin-bottom: 8px;
         }

         .school-address {
             font-size: 14px;
             color: #666;
             margin-bottom: 15px;
         }

         .calendar-title {
             font-size: 22px;
             font-weight: 600;
             color: #2563eb;
             margin-top: 10px;
         }

         /* Months Grid */
         .months-grid {
             display: grid;
             grid-template-columns: repeat(2, 1fr);
             gap: 35px;
             margin-bottom: 50px;
         }

         /* Month Card */
         .month-card {
             background: #ffffff;
             border: 1px solid #e0e0e0;
             border-radius: 12px;
             overflow: hidden;
             page-break-inside: avoid;
             margin-bottom: 10px;
         }

         .month-header {
             background: #2563eb;
             color: #ffffff;
             padding: 16px 24px;
             font-size: 20px;
             font-weight: 700;
             letter-spacing: 0.5px;
         }

         .month-body {
             padding: 20px;
         }

         /* Calendar Item */
         .calendar-item {
             display: flex;
             align-items: flex-start;
             padding: 14px 16px;
             margin-bottom: 12px;
             border-radius: 8px;
             background: #f9fafb;
             border-left: 4px solid #e5e7eb;
             transition: all 0.2s;
         }

         .calendar-item:last-child {
             margin-bottom: 0;
         }

         .calendar-item.holiday {
             background: #fef2f2;
             border-left-color: #dc2626;
         }

         .calendar-item.exam {
             background: #fffbeb;
             border-left-color: #f59e0b;
         }

         .calendar-item.event {
             background: #f0fdf4;
             border-left-color: #16a34a;
         }

         .item-left {
             display: flex;
             align-items: center;
             gap: 16px;
             flex: 1;
         }

         .item-date {
             font-size: 13px;
             font-weight: 700;
             color: #6b7280;
             min-width: 65px;
             text-align: left;
         }

         .item-title {
             font-size: 14px;
             color: #1f2937;
             font-weight: 500;
             line-height: 1.4;
         }

         .item-badge {
             font-size: 10px;
             padding: 4px 10px;
             border-radius: 6px;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             white-space: nowrap;
             margin-left: auto;
         }

         .item-badge.badge-holiday {
             background: #dc2626;
             color: #ffffff;
         }

         .item-badge.badge-exam {
             background: #f59e0b;
             color: #ffffff;
         }

         .item-badge.badge-event {
             background: #16a34a;
             color: #ffffff;
         }

         /* Empty State */
         .empty-month {
             text-align: center;
             padding: 30px 20px;
             color: #9ca3af;
             font-size: 14px;
             font-style: italic;
         }

         /* Legend */
         .legend-section {
             margin-top: 40px;
             padding-top: 30px;
             border-top: 2px solid #e8e8e8;
         }

         .legend-title {
             font-size: 16px;
             font-weight: 600;
             color: #1f2937;
             margin-bottom: 20px;
             text-align: center;
         }

         .legend-grid {
             display: flex;
             justify-content: center;
             gap: 40px;
             flex-wrap: wrap;
         }

         .legend-item {
             display: flex;
             align-items: center;
             gap: 12px;
         }

         .legend-dot {
             width: 16px;
             height: 16px;
             border-radius: 4px;
             flex-shrink: 0;
         }

         .legend-dot.holiday {
             background: #dc2626;
         }

         .legend-dot.exam {
             background: #f59e0b;
         }

         .legend-dot.event {
             background: #16a34a;
         }

         .legend-label {
             font-size: 14px;
             color: #4b5563;
             font-weight: 500;
         }

         /* Footer */
         .footer {
             margin-top: 50px;
             text-align: center;
             font-size: 12px;
             color: #9ca3af;
             padding-top: 20px;
             border-top: 1px solid #e8e8e8;
         }

         /* Print Styles */
         @media print {
             body {
                 padding: 20px;
             }

             .month-card {
                 box-shadow: none;
             }

             .months-grid {
                 gap: 30px;
             }
         }

         @page {
             margin: 1cm;
         }
     </style>
 </head>

 <body>
     <!-- Header -->
     <div class="header">
         @if ($logo)
             <img src="{{ $logo }}" alt="School Logo" class="logo">
         @endif
         <div class="school-name">{{ $school_name }}</div>
         <div class="school-address">{{ $school_address }}</div>
         <div class="calendar-title">Academic Calendar {{ $report_year }}</div>
     </div>

     <!-- Calendar Grid -->
     <div class="months-grid">
         @foreach ($calendar_items_by_month as $month => $items)
             <div class="month-card">
                 <div class="month-header">{{ $month }}</div>
                 <div class="month-body">
                     @if (count($items) > 0)
                         @foreach ($items as $item)
                             @php
                                 $itemDate = is_object($item) ? $item->date : $item['date'];
                                 $itemTitle = is_object($item) ? $item->title : $item['title'];
                                 $itemType = is_object($item) ? $item->type : $item['type'];
                             @endphp
                             <div class="calendar-item {{ $itemType }}">
                                 <div class="item-left">
                                     <div class="item-date">
                                         {{ \Carbon\Carbon::parse($itemDate)->format('d M Y') }}
                                     </div>
                                     <div class="item-title">{{ $itemTitle }}</div>
                                 </div>
                                 <span class="item-badge badge-{{ $itemType }}">
                                     {{ ucfirst($itemType) }}
                                 </span>
                             </div>
                         @endforeach
                     @else
                         <div class="empty-month">No events scheduled</div>
                     @endif
                 </div>
             </div>
         @endforeach
     </div>

     <!-- Legend -->
     {{-- <div class="legend-section">
         <div class="legend-title">Legend</div>
         <div class="legend-grid">
             <div class="legend-item">
                 <div class="legend-dot holiday"></div>
                 <span class="legend-label">Holidays</span>
             </div>
             <div class="legend-item">
                 <div class="legend-dot exam"></div>
                 <span class="legend-label">Examinations</span>
             </div>
             <div class="legend-item">
                 <div class="legend-dot event"></div>
                 <span class="legend-label">Events</span>
             </div>
         </div>
     </div> --}}

     <!-- Footer -->
     <div class="footer">
         Generated on {{ now()->format('d F Y') }} | {{ $school_name }}
     </div>
 </body>

 </html>
