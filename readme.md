# 简介
  使用laravoole框架作为WebSocket服务端提供遗传算法的演算服务
  
+ 支持多开端,不同客户端独立演算
  
# 部署
```bash
    git clone https://github.com/Jezzis/laravoole_tsp.git
    cd ~/laravoole_tsp
    composer install 
    php artisan laravoole start
    php artisan serve --port=8081
```    

然后访问 http://localhost:8081/index.html
  
# Demo
![Demo](./resources/doc/demo.gif)

# License
本项目开源且遵守[MIT license](http://opensource.org/licenses/MIT).