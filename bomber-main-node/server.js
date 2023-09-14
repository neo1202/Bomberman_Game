const express = require('express');
const path = require('path');
const app = express();
const PORT = 3000;

// 設置靜態檔案目錄
app.use(express.static(path.join(__dirname, 'public')));

// 如果上面的靜態檔案路由沒有回應，那麼這個路由就會執行
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
    console.log(`Server is running at http://localhost:${PORT}`);
});