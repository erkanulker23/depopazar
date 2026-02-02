import { DataSource } from 'typeorm';
import { config } from 'dotenv';
import databaseConfig from '../../config/database.config';
import { User } from '../../modules/users/entities/user.entity';
import { Company } from '../../modules/companies/entities/company.entity';
import { Warehouse } from '../../modules/warehouses/entities/warehouse.entity';
import { Room } from '../../modules/rooms/entities/room.entity';
import { Customer } from '../../modules/customers/entities/customer.entity';
import { Contract } from '../../modules/contracts/entities/contract.entity';
import { Payment } from '../../modules/payments/entities/payment.entity';
import { Item } from '../../modules/items/entities/item.entity';
import { Notification } from '../../modules/notifications/entities/notification.entity';
import { UserRole } from '../../common/enums/user-role.enum';
import { RoomStatus } from '../../common/enums/room-status.enum';
import { PaymentStatus } from '../../common/enums/payment-status.enum';
import { NotificationType } from '../../common/enums/notification-type.enum';

config();

async function runSeeds() {
  const dataSource = await databaseConfig.initialize();

  try {
    const userRepo = dataSource.getRepository(User);
    const companyRepo = dataSource.getRepository(Company);
    const warehouseRepo = dataSource.getRepository(Warehouse);
    const roomRepo = dataSource.getRepository(Room);
    const customerRepo = dataSource.getRepository(Customer);
    const contractRepo = dataSource.getRepository(Contract);
    const paymentRepo = dataSource.getRepository(Payment);
    const itemRepo = dataSource.getRepository(Item);
    const notificationRepo = dataSource.getRepository(Notification);

    // 1. Super Admin (varsa şifreyi de "password" yaparak güncelle; yoksa oluştur)
    const superAdminEmail = 'erkanulker0@gmail.com';
    const superAdminPlainPassword = 'password';
    let superAdmin = await userRepo.findOne({
      where: { email: superAdminEmail },
    });

    if (!superAdmin) {
      superAdmin = userRepo.create({
        email: superAdminEmail,
        password: superAdminPlainPassword,
        first_name: 'Super',
        last_name: 'Admin',
        role: UserRole.SUPER_ADMIN,
        is_active: true,
      });
      await superAdmin.hashPassword();
      await userRepo.save(superAdmin);
      console.log('✅ Super Admin created (email: %s, şifre: %s)', superAdminEmail, superAdminPlainPassword);
    } else {
      superAdmin.password = superAdminPlainPassword;
      await superAdmin.hashPassword();
      await userRepo.save(superAdmin);
      console.log('✅ Super Admin şifre sıfırlandı (email: %s, şifre: %s)', superAdminEmail, superAdminPlainPassword);
    }

    // 2. Demo Company (önce firma oluşturulur; super_admin buna atanacak)
    let demoCompany = await companyRepo.findOne({
      where: { slug: 'demo-depo' },
    });

    if (!demoCompany) {
      demoCompany = companyRepo.create({
        name: 'Demo Depo Firması',
        slug: 'demo-depo',
        email: 'info@demodepo.com',
        phone: '+905551234567',
        address: 'İstanbul, Türkiye',
        package_type: 'premium',
        max_warehouses: 10,
        max_rooms: 100,
        max_customers: 500,
        is_active: true,
      });
      await companyRepo.save(demoCompany);
      console.log('✅ Demo Company created');
    }

    // Super Admin'a demo firmayı ata (Ayarlar sayfası 404 vermesin)
    if (!superAdmin.company_id || superAdmin.company_id !== demoCompany.id) {
      superAdmin.company_id = demoCompany.id;
      await userRepo.save(superAdmin);
      console.log('✅ Super Admin demo firmaya atandı');
    }

    // 3. Company Owner
    let companyOwner = await userRepo.findOne({
      where: { email: 'owner@demodepo.com' },
    });

    if (!companyOwner) {
      companyOwner = userRepo.create({
        email: 'owner@demodepo.com',
        password: 'password',
        first_name: 'Depo',
        last_name: 'Sahibi',
        role: UserRole.COMPANY_OWNER,
        company_id: demoCompany.id,
        is_active: true,
      });
      await companyOwner.hashPassword();
      await userRepo.save(companyOwner);
      console.log('✅ Company Owner created');
    } else if (companyOwner.company_id !== demoCompany.id) {
      companyOwner.company_id = demoCompany.id;
      await userRepo.save(companyOwner);
      console.log('✅ Company Owner company_id synced to demo company');
    }

    // 4. Company Staff
    let staff = await userRepo.findOne({
      where: { email: 'staff@demodepo.com' },
    });

    if (!staff) {
      staff = userRepo.create({
        email: 'staff@demodepo.com',
        password: 'password',
        first_name: 'Depo',
        last_name: 'Personeli',
        role: UserRole.COMPANY_STAFF,
        company_id: demoCompany.id,
        is_active: true,
      });
      await staff.hashPassword();
      await userRepo.save(staff);
      console.log('✅ Company Staff created');
    } else if (staff.company_id !== demoCompany.id) {
      staff.company_id = demoCompany.id;
      await userRepo.save(staff);
      console.log('✅ Company Staff company_id synced to demo company');
    }

    // 5. Warehouses
    let warehouse1 = await warehouseRepo.findOne({
      where: { name: 'Ana Depo' },
    });

    if (!warehouse1) {
      warehouse1 = warehouseRepo.create({
        name: 'Ana Depo',
        company_id: demoCompany.id,
        address: 'İstanbul, Şişli, Depo Sokak No:1',
        city: 'İstanbul',
        district: 'Şişli',
        total_floors: 3,
        description: 'Ana depo binası',
        is_active: true,
      });
      await warehouseRepo.save(warehouse1);
      console.log('✅ Warehouse 1 created');
    }

    let warehouse2 = await warehouseRepo.findOne({
      where: { name: 'Yedek Depo' },
    });

    if (!warehouse2) {
      warehouse2 = warehouseRepo.create({
        name: 'Yedek Depo',
        company_id: demoCompany.id,
        address: 'İstanbul, Kadıköy, Depo Caddesi No:5',
        city: 'İstanbul',
        district: 'Kadıköy',
        total_floors: 2,
        description: 'Yedek depo binası',
        is_active: true,
      });
      await warehouseRepo.save(warehouse2);
      console.log('✅ Warehouse 2 created');
    }

    // 6. Rooms - Ana Depo'da 30 oda (3 kat x 10 oda)
    const rooms = [];
    for (let floor = 1; floor <= 3; floor++) {
      for (let roomNum = 1; roomNum <= 10; roomNum++) {
        const roomNumber = `A-${floor}${String(roomNum).padStart(2, '0')}`;
        let room = await roomRepo.findOne({
          where: { room_number: roomNumber, warehouse_id: warehouse1.id },
        });

        if (!room) {
          // İlk kat: ilk 5 oda dolu, 2. kat: ilk 3 oda dolu, 3. kat: hepsi boş
          const isOccupied = (floor === 1 && roomNum <= 5) || (floor === 2 && roomNum <= 3);
          room = roomRepo.create({
            room_number: roomNumber,
            warehouse_id: warehouse1.id,
            area_m2: Number((10 + Math.random() * 5).toFixed(2)), // 10-15 m²
            monthly_price: Number((500 + Math.random() * 200).toFixed(2)), // 500-700 TL
            status: isOccupied ? RoomStatus.OCCUPIED : RoomStatus.EMPTY,
            floor: floor.toString(),
            block: 'A',
            corridor: floor === 1 ? 'Kuzey' : floor === 2 ? 'Güney' : 'Doğu',
            description: `${roomNumber} numaralı oda`,
          });
          await roomRepo.save(room);
          rooms.push(room);
        } else {
          rooms.push(room);
        }
      }
    }
    console.log(`✅ ${rooms.length} rooms created`);

    // 7. Customers
    const customerNames = [
      { first: 'Ahmet', last: 'Yılmaz', email: 'ahmet@example.com' },
      { first: 'Ayşe', last: 'Demir', email: 'ayse@example.com' },
      { first: 'Mehmet', last: 'Kaya', email: 'mehmet@example.com' },
      { first: 'Fatma', last: 'Şahin', email: 'fatma@example.com' },
      { first: 'Ali', last: 'Çelik', email: 'ali@example.com' },
    ];

    const customers = [];
    for (const name of customerNames) {
      let customer = await customerRepo.findOne({
        where: { email: name.email },
      });

      if (!customer) {
        customer = customerRepo.create({
          company_id: demoCompany.id,
          first_name: name.first,
          last_name: name.last,
          email: name.email,
          phone: `+90555${Math.floor(1000000 + Math.random() * 9000000)}`,
          identity_number: `${Math.floor(10000000000 + Math.random() * 90000000000)}`,
          address: 'İstanbul, Türkiye',
          is_active: true,
        });
        await customerRepo.save(customer);
        customers.push(customer);
      } else {
        customers.push(customer);
      }
    }
    console.log(`✅ ${customers.length} customers created`);

    // 7.5. Customer Users - Müşteriler için user kayıtları oluştur (giriş yapabilmeleri için)
    for (const customer of customers) {
      let customerUser = await userRepo.findOne({
        where: { email: customer.email },
      });

      if (!customerUser) {
        customerUser = userRepo.create({
          email: customer.email,
          password: 'password', // Varsayılan şifre: password
          first_name: customer.first_name,
          last_name: customer.last_name,
          phone: customer.phone,
          role: UserRole.CUSTOMER,
          company_id: customer.company_id,
          is_active: true,
        });
        await customerUser.hashPassword();
        await userRepo.save(customerUser);
      }
    }
    console.log(`✅ ${customers.length} customer users created`);

    // 8. Contracts - İlk 5 müşteri için sözleşme (A-101, A-102, A-103, A-104, A-105)
    const contracts = [];
    const occupiedRooms = rooms.filter(r => r.status === RoomStatus.OCCUPIED).slice(0, 5);
    
    for (let i = 0; i < Math.min(5, customers.length, occupiedRooms.length); i++) {
      const customer = customers[i];
      const room = occupiedRooms[i];
      const contractNumber = `CNT-2024-${String(i + 1).padStart(3, '0')}`;

      let contract = await contractRepo.findOne({
        where: { contract_number: contractNumber },
      });

      if (!contract) {
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - i);
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + 12);

        contract = contractRepo.create({
          contract_number: contractNumber,
          customer_id: customer.id,
          room_id: room.id,
          start_date: startDate,
          end_date: endDate,
          monthly_price: room.monthly_price,
          payment_frequency_months: 1,
          terms: 'Standart depolama sözleşmesi',
          is_active: true,
        });
        await contractRepo.save(contract);
        contracts.push(contract);
      } else {
        contracts.push(contract);
      }
    }
    console.log(`✅ ${contracts.length} contracts created`);

    // 9. Payments - Her sözleşme için son 3 ayın ödemeleri
    let paymentCount = 0;
    for (let contractIdx = 0; contractIdx < contracts.length; contractIdx++) {
      const contract = contracts[contractIdx];
      // Her sözleşme için son 3 ay için ödeme oluştur
      for (let month = 0; month < 3; month++) {
        const dueDate = new Date(contract.start_date);
        dueDate.setMonth(dueDate.getMonth() + month);
        const paymentNumber = `PAY-2024-${String(paymentCount + 1).padStart(3, '0')}`;

        let payment = await paymentRepo.findOne({
          where: { payment_number: paymentNumber },
        });

        if (!payment) {
          // İlk 2 ay ödendi, son ay duruma göre
          const isPaid = month < 2;
          const paidAt = isPaid ? new Date(dueDate.getTime() + (3 + Math.random() * 5) * 24 * 60 * 60 * 1000) : null;
          const now = new Date();
          const daysOverdue = !isPaid && now > dueDate 
            ? Math.floor((now.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24))
            : 0;
          
          // Bazı ödemeler gecikmiş olabilir
          let status = PaymentStatus.PENDING;
          if (isPaid) {
            status = PaymentStatus.PAID;
          } else if (daysOverdue > 0) {
            status = PaymentStatus.OVERDUE;
          } else if (contractIdx === 1 || contractIdx === 3) {
            // 2. ve 4. sözleşmelerin son ödemesi gecikmiş
            status = PaymentStatus.OVERDUE;
          }

          const paymentMethods = ['credit_card', 'bank_transfer', 'cash'];
          payment = paymentRepo.create({
            payment_number: paymentNumber,
            contract_id: contract.id,
            amount: contract.monthly_price,
            status: status,
            due_date: dueDate,
            paid_at: paidAt,
            payment_method: isPaid ? paymentMethods[Math.floor(Math.random() * paymentMethods.length)] : null,
            days_overdue: status === PaymentStatus.OVERDUE ? daysOverdue : 0,
          });
          await paymentRepo.save(payment);
          paymentCount++;
        }
      }
    }
    console.log(`✅ ${paymentCount} payments created`);

    // 10. Items
    const itemNames = [
      'Mobilya Seti',
      'Elektronik Eşyalar',
      'Kitaplar',
      'Kıyafetler',
      'Ev Eşyaları',
      'Spor Malzemeleri',
      'Müzik Aletleri',
      'Sanat Eserleri',
    ];

    let itemCount = 0;
    for (let i = 0; i < Math.min(contracts.length, 3); i++) {
      const contract = contracts[i];
      const room = rooms[i];
      const itemsForRoom = itemNames.slice(0, 3 + Math.floor(Math.random() * 3));

      for (const itemName of itemsForRoom) {
        let item = await itemRepo.findOne({
          where: { name: itemName, room_id: room.id },
        });

        if (!item) {
          item = itemRepo.create({
            room_id: room.id,
            contract_id: contract.id,
            name: itemName,
            description: `${itemName} için detaylı açıklama`,
            quantity: Math.floor(1 + Math.random() * 10),
            unit: 'adet',
            stored_at: contract.start_date,
            notes: 'Güvenli şekilde depolanmıştır',
          });
          await itemRepo.save(item);
          itemCount++;
        }
      }
    }
    console.log(`✅ ${itemCount} items created`);

    // 11. Notifications
    const notifications = [];
    
    // Overdue payment notifications
    const overduePayments = await paymentRepo.find({
      where: { status: PaymentStatus.OVERDUE },
    });

    for (const payment of overduePayments.slice(0, 3)) {
      const contract = await contractRepo.findOne({
        where: { id: payment.contract_id },
        relations: ['customer'],
      });

      if (contract && contract.customer) {
        let notification = await notificationRepo.findOne({
          where: {
            customer_id: contract.customer.id,
            type: NotificationType.PAYMENT_OVERDUE,
            metadata: { payment_id: payment.id } as any,
          },
        });

        if (!notification) {
          notification = notificationRepo.create({
            customer_id: contract.customer.id,
            type: NotificationType.PAYMENT_OVERDUE,
            title: 'Geciken Ödeme',
            message: `${payment.payment_number} numaralı ödemeniz ${payment.days_overdue} gün gecikmiştir.`,
            is_read: false,
            metadata: { payment_id: payment.id, contract_id: contract.id },
          });
          await notificationRepo.save(notification);
          notifications.push(notification);
        }
      }
    }

    // Contract expiring notifications
    const expiringContracts = await contractRepo.find({
      where: { is_active: true },
    });

    for (const contract of expiringContracts.slice(0, 2)) {
      const daysUntilExpiry = Math.floor(
        (contract.end_date.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
      );

      if (daysUntilExpiry <= 30 && daysUntilExpiry > 0) {
        let notification = await notificationRepo.findOne({
          where: {
            customer_id: contract.customer_id,
            type: NotificationType.CONTRACT_EXPIRING,
            metadata: { contract_id: contract.id } as any,
          },
        });

        if (!notification) {
          notification = notificationRepo.create({
            customer_id: contract.customer_id,
            type: NotificationType.CONTRACT_EXPIRING,
            title: 'Sözleşme Sonu Yaklaşıyor',
            message: `Sözleşmeniz ${daysUntilExpiry} gün sonra sona erecek.`,
            is_read: false,
            metadata: { contract_id: contract.id },
          });
          await notificationRepo.save(notification);
          notifications.push(notification);
        }
      }
    }

    console.log(`✅ ${notifications.length} notifications created`);

    console.log('');
    console.log('✅ Seed data created successfully!');
    console.log('');
    console.log('📊 Özet:');
    console.log(`   - 1 Super Admin`);
    console.log(`   - 1 Company`);
    console.log(`   - 2 Users (Owner, Staff)`);
    console.log(`   - 2 Warehouses`);
    console.log(`   - ${rooms.length} Rooms`);
    console.log(`   - ${customers.length} Customers`);
    console.log(`   - ${contracts.length} Contracts`);
    console.log(`   - ${paymentCount} Payments`);
    console.log(`   - ${itemCount} Items`);
    console.log(`   - ${notifications.length} Notifications`);
  } catch (error) {
    console.error('❌ Error running seeds:', error);
    throw error;
  } finally {
    await dataSource.destroy();
  }
}

runSeeds();
