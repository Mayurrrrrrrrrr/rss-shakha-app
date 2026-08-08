const Chart = require('chart.js'); // Assuming node env for simple syntax check
const catData = [{"category":"","cnt":279},{"category":"संगठन","cnt":53},{"category":"जागरण","cnt":39},{"category":"गतिविधी","cnt":6}];
const extractData = (dataArray, labelKey, valueKey) => {
    return {
        labels: dataArray.map(item => item[labelKey]),
        values: dataArray.map(item => item[valueKey])
    };
};
const catExtracted = extractData(catData, 'category', 'cnt');
console.log(catExtracted);
