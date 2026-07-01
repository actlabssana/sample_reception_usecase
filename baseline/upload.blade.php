<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Reception - Document Upload</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --background-light: #f8fafc;
            --border-color: #e2e8f0;
        }
        
        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .upload-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .upload-card h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-button {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f1f5f9;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-input-button:hover {
            border-color: var(--primary-color);
            background: #e0f2fe;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .results-container {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 1200px) {
            .results-container {
                grid-template-columns: 1fr;
            }
        }
        
        .data-panel {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .data-panel::-webkit-scrollbar {
            width: 8px;
        }
        
        .data-panel::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .data-panel::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }
        
        .preview-panel {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .section-header {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-header:first-child {
            margin-top: 0;
        }
        
        .field-group {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .field-group label {
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.875rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .field-group textarea {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.875rem;
            resize: vertical;
            min-height: 60px;
        }
        
        .field-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .document-viewer {
            width: 100%;
            min-height: 75vh;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
        }
        
        .secondary-document {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border-color);
        }
        
        .secondary-document .document-viewer {
            min-height: 60vh;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            font-size: 0.875rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
        }
        
        .preview-table th {
            padding: 0.875rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .preview-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .preview-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .preview-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-wrap {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .badge-custom {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .alert-info {
            background: #e0f2fe;
            border-color: #bae6fd;
            color: #075985;
            border-radius: 8px;
        }
        
        .filename-display {
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <h1 class="mb-0">
                <i class="bi bi-file-earmark-arrow-up"></i>
                Sample Reception System
            </h1>
            <p class="mb-0 mt-2 opacity-75">Upload and process laboratory sample documents</p>
        </div>
    </div>

    <div class="container">
        <div class="upload-card">
            <h3>
                <i class="bi bi-cloud-upload"></i>
                Upload Documents
            </h3>
            
            <form action="{{ route('sample.parse') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="bi bi-file-earmark-pdf"></i>
                            Main Document <span class="text-danger">*</span>
                        </label>
                        <div class="file-input-wrapper">
                            <div class="file-input-button">
                                <i class="bi bi-file-earmark-arrow-up fs-2 text-primary"></i>
                                <p class="mb-0 mt-2">Click to select main document</p>
                                <small class="text-muted">PDF, DOC, DOCX, XLS, XLSX, CSV (max 20MB)</small>
                            </div>
                            <input type="file" name="form_file_main" class="form-control" required 
                                   accept=".pdf,.doc,.docx,.xlsx,.xls,.csv">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            <i class="bi bi-file-earmark-plus"></i>
                            Secondary Document <span class="text-muted">(Optional)</span>
                        </label>
                        <div class="file-input-wrapper">
                            <div class="file-input-button">
                                <i class="bi bi-file-earmark-plus fs-2 text-secondary"></i>
                                <p class="mb-0 mt-2">Click to select secondary document</p>
                                <small class="text-muted">PDF, XLS, XLSX, CSV (max 20MB)</small>
                            </div>
                            <input type="file" name="form_file_secondary" class="form-control" 
                                   accept=".pdf,.xlsx,.xls,.csv">
                        </div>
                    </div>
                </div>
                
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Error:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-upload"></i>
                        Process Documents
                    </button>
                </div>
            </form>
        </div>

        @if(isset($mainParsed))
        <div class="results-container">
            <!-- Left Panel: Extracted Data -->
            <div class="data-panel">
                <div class="section-header">
                    <i class="bi bi-file-text"></i>
                    Main Document Fields
                </div>
                
                @if(isset($mainFilename))
                    <div class="filename-display">
                        <i class="bi bi-file-earmark-check"></i>
                        {{ $mainFilename }}
                    </div>
                @endif
                
                @foreach($mainParsed as $key => $value)
                    @if(!is_array($value) && $value !== '')
                        <div class="field-group">
                            <label>{{ $key }}</label>
                            <textarea rows="2" class="form-control">{{ $value }}</textarea>
                        </div>
                    @endif
                @endforeach

                @php $mainSamples = $mainParsed['Sample Data'] ?? []; @endphp
                @if(!empty($mainSamples))
                    <div class="section-header">
                        <i class="bi bi-table"></i>
                        Main — Sample Data
                        <span class="badge-custom">{{ count($mainSamples) }} samples</span>
                    </div>
                    {!! \App\Http\Controllers\HtmlPreview::tableFromSampleData($mainSamples) !!}

                    @if(!empty($mainParsed['Prep Code Counts']) || !empty($mainParsed['Analysis Code Counts']))
                        <div class="section-header">
                            <i class="bi bi-bar-chart"></i>
                            Code Summary
                        </div>
                        <div class="table-wrap">
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th>Code Type</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach(($mainParsed['Prep Code Counts'] ?? []) as $c => $n)
                                    <tr>
                                        <td><strong>Prep {{ $c }}</strong></td>
                                        <td><span class="badge-custom">{{ $n }}</span></td>
                                    </tr>
                                @endforeach
                                @foreach(($mainParsed['Analysis Code Counts'] ?? []) as $c => $n)
                                    <tr>
                                        <td><strong>{{ $c }}</strong></td>
                                        <td><span class="badge-custom">{{ $n }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                @if(isset($secondaryParsed) && count($secondaryParsed))
                    <div class="section-header">
                        <i class="bi bi-file-text"></i>
                        Secondary Document Fields
                    </div>
                    
                    @if(isset($secondaryFilename))
                        <div class="filename-display">
                            <i class="bi bi-file-earmark-check"></i>
                            {{ $secondaryFilename }}
                        </div>
                    @endif
                    
                    @foreach($secondaryParsed as $key => $value)
                        @if(!is_array($value) && $value !== '')
                            <div class="field-group">
                                <label>{{ $key }}</label>
                                <textarea rows="2" class="form-control">{{ $value }}</textarea>
                            </div>
                        @endif
                    @endforeach

                    @php $secSamples = $secondaryParsed['Sample Data'] ?? []; @endphp
                    @if(!empty($secSamples))
                        <div class="section-header">
                            <i class="bi bi-table"></i>
                            Secondary — Sample Data
                            <span class="badge-custom">{{ count($secSamples) }} samples</span>
                        </div>
                        {!! \App\Http\Controllers\HtmlPreview::tableFromSampleData($secSamples) !!}

                        @if(!empty($secondaryParsed['Prep Code Counts']) || !empty($secondaryParsed['Analysis Code Counts']))
                            <div class="section-header">
                                <i class="bi bi-bar-chart"></i>
                                Code Summary
                            </div>
                            <div class="table-wrap">
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>Code Type</th>
                                            <th>Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach(($secondaryParsed['Prep Code Counts'] ?? []) as $c => $n)
                                        <tr>
                                            <td><strong>Prep {{ $c }}</strong></td>
                                            <td><span class="badge-custom">{{ $n }}</span></td>
                                        </tr>
                                    @endforeach
                                    @foreach(($secondaryParsed['Analysis Code Counts'] ?? []) as $c => $n)
                                        <tr>
                                            <td><strong>{{ $c }}</strong></td>
                                            <td><span class="badge-custom">{{ $n }}</span></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                @endif
            </div>

            <!-- Right Panel: Document Preview -->
            <div class="preview-panel">
                <div class="section-header">
                    <i class="bi bi-eye"></i>
                    Main Document Preview
                </div>
                
                @if(isset($mainFilename))
                    <div class="filename-display mb-3">
                        <i class="bi bi-file-earmark"></i>
                        {{ $mainFilename }}
                    </div>
                @endif
                
                {!! $mainPreview ?? '<div class="alert alert-info">No preview available</div>' !!}
                
                @if(isset($secondaryPreview))
                    <div class="secondary-document">
                        <div class="section-header">
                            <i class="bi bi-eye"></i>
                            Secondary Document Preview
                        </div>
                        
                        @if(isset($secondaryFilename))
                            <div class="filename-display mb-3">
                                <i class="bi bi-file-earmark"></i>
                                {{ $secondaryFilename }}
                            </div>
                        @endif
                        
                        {!! $secondaryPreview !!}
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // File input handling with preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    const wrapper = this.closest('.file-input-wrapper');
                    const button = wrapper.querySelector('.file-input-button');
                    button.innerHTML = `
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                        <p class="mb-0 mt-2"><strong>${fileName}</strong></p>
                        <small class="text-muted">Click to change file</small>
                    `;
                }
            });
        });
    </script>
</body>
</html>
