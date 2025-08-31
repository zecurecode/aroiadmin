<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCKasse WSDL - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .info-section {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: #f8f9ff;
            border: 1px solid #e1e5f2;
            border-radius: 6px;
            padding: 20px;
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #4c63d2;
            font-size: 1.2em;
        }
        .info-card p {
            margin: 0;
            color: #666;
        }
        .info-card code {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
            word-break: break-all;
        }
        .actions {
            padding: 20px 30px;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px 10px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #4c63d2;
            color: white;
        }
        .btn-primary:hover {
            background: #3b4fd8;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .xml-container {
            background: #2d3748;
            color: #e2e8f0;
            padding: 0;
            margin: 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            max-height: 600px;
            overflow-y: auto;
        }
        .xml-content {
            padding: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .xml-content .tag { color: #63b3ed; }
        .xml-content .attr-name { color: #fbb6ce; }
        .xml-content .attr-value { color: #68d391; }
        .xml-content .text { color: #e2e8f0; }
        .xml-content .comment { color: #a0aec0; font-style: italic; }
        .toggle-section {
            border-top: 1px solid #eee;
        }
        .toggle-header {
            padding: 20px 30px;
            background: #f9f9f9;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .toggle-header:hover {
            background: #f0f0f0;
        }
        .toggle-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        .toggle-content {
            display: none;
        }
        .toggle-content.active {
            display: block;
        }
        .footer {
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #eee;
        }
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #28a745;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 PCKasse WSDL</h1>
            <p>SOAP Web Service for PCKasse Integration</p>
            <div class="status-indicator"></div>Active
        </div>

        <div class="info-section">
            <h2>📡 Endpoint Information</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3>SOAP Endpoint</h3>
                    <p><code>{{ $endpoint_url }}</code></p>
                </div>
                <div class="info-card">
                    <h3>WSDL URL</h3>
                    <p><code>{{ $wsdl_url }}</code></p>
                </div>
                <div class="info-card">
                    <h3>Version</h3>
                    <p>PCKasse WebshopWS v1.98</p>
                </div>
                <div class="info-card">
                    <h3>Protocol</h3>
                    <p>SOAP 1.1 / RPC Encoded</p>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ $wsdl_url }}?download=1" class="btn btn-primary" download="pck.wsdl">
                📥 Last ned WSDL
            </a>
            <a href="/pck/health" class="btn btn-success" target="_blank">
                ❤️ Helsesjekk
            </a>
            <button onclick="copyToClipboard('{{ $wsdl_url }}')" class="btn btn-secondary">
                📋 Kopier WSDL URL
            </button>
            <button onclick="toggleXml()" class="btn btn-secondary" id="toggleBtn">
                👁️ Vis WSDL Kilde
            </button>
        </div>

        <div class="toggle-section">
            <div class="toggle-header" onclick="toggleOperations()">
                <h2>🔧 Tilgjengelige SOAP Operasjoner</h2>
                <span class="toggle-arrow">▼</span>
            </div>
            <div class="toggle-content active" id="operations">
                <div style="padding: 20px 30px;">
                    <div class="info-grid">
                        <div class="info-card">
                            <h3>📦 sendArticle</h3>
                            <p>Send produktdata fra PCK til WooCommerce</p>
                        </div>
                        <div class="info-card">
                            <h3>🖼️ sendImage</h3>
                            <p>Last opp produktbilder til webshop</p>
                        </div>
                        <div class="info-card">
                            <h3>📊 updateStockCount</h3>
                            <p>Oppdater lagernivåer fra PCK</p>
                        </div>
                        <div class="info-card">
                            <h3>🛒 getOrders</h3>
                            <p>Hent nye ordrer fra webshop</p>
                        </div>
                        <div class="info-card">
                            <h3>✅ updateOrderStatus</h3>
                            <p>Oppdater ordrestatus fra PCK</p>
                        </div>
                        <div class="info-card">
                            <h3>🗑️ removeArticle</h3>
                            <p>Fjern produkt fra webshop</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="toggle-section">
            <div class="toggle-header" onclick="toggleXml()">
                <h2>📄 WSDL Kildekode</h2>
                <span class="toggle-arrow" id="xmlArrow">▶</span>
            </div>
            <div class="toggle-content" id="xmlSection">
                <div class="xml-container">
                    <div class="xml-content" id="xmlContent">{{ $wsdl_content }}</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>🚀 Aroi PCKasse Integration | Laravel SOAP Service</p>
            <p>For support, kontakt systemadministrator</p>
        </div>
    </div>

    <script>
        function toggleXml() {
            const section = document.getElementById('xmlSection');
            const arrow = document.getElementById('xmlArrow');
            const btn = document.getElementById('toggleBtn');
            
            if (section.classList.contains('active')) {
                section.classList.remove('active');
                arrow.textContent = '▶';
                btn.textContent = '👁️ Vis WSDL Kilde';
            } else {
                section.classList.add('active');
                arrow.textContent = '▼';
                btn.textContent = '🙈 Skjul WSDL Kilde';
                highlightXML();
            }
        }

        function toggleOperations() {
            const section = document.getElementById('operations');
            const arrow = section.previousElementSibling.querySelector('.toggle-arrow');
            
            if (section.classList.contains('active')) {
                section.classList.remove('active');
                arrow.textContent = '▶';
            } else {
                section.classList.add('active');
                arrow.textContent = '▼';
            }
        }

        function highlightXML() {
            const content = document.getElementById('xmlContent');
            let xml = content.textContent;
            
            // Simple XML syntax highlighting
            xml = xml.replace(/(&lt;[^&]*&gt;)/g, '<span class="tag">$1</span>');
            xml = xml.replace(/(\w+)=/g, '<span class="attr-name">$1</span>=');
            xml = xml.replace(/="([^"]*)"/g, '="<span class="attr-value">$1</span>"');
            xml = xml.replace(/(&lt;!--.*?--&gt;)/gs, '<span class="comment">$1</span>');
            
            content.innerHTML = xml;
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('WSDL URL kopiert til utklippstavle!');
            }, function(err) {
                console.error('Kunne ikke kopiere: ', err);
            });
        }

        // Add download parameter to WSDL URL when requested directly as XML
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('download')) {
                // Force download by changing content type
                window.location.href = '{{ $wsdl_url }}';
            }
        });
    </script>
</body>
</html>